<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Bridge\File\HttpHandlerDumpDirectoryPathProviderInterface;
use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlingActorInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Handler\HttpMiddlewareChainHandler;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageFormatterContract;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final class HttpHandleService implements HttpHandleServiceInterface
{
    /**
     * @var array<array-key, HttpHandlerStackInterface|null>
     */
    private array $stackCache = [];

    /**
     * @var array<array-key, HttpHandleContextInterface>
     */
    private array $contextCache = [];

    private HttpHandlingActorInterface $actor;

    private HttpHandleContextFactoryInterface $contextFactory;

    private LoggerInterface $logger;

    private HttpHandlerStackBuilderFactoryInterface $stackBuilderFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ResponseFactoryInterface $responseFactory;

    private WebHttpHandlerConfigurationFindActionInterface $webHttpHandlerConfigurationFindAction;

    private Psr7MessageFormatterContract $psr7MessageLogFormatter;

    private HttpHandlerDumpDirectoryPathProviderInterface $httpHandlerDumpDirectoryPathProvider;

    public function __construct(
        HttpHandlingActorInterface $actor,
        HttpHandleContextFactoryInterface $contextFactory,
        LoggerInterface $logger,
        HttpHandlerStackBuilderFactoryInterface $stackBuilderFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ResponseFactoryInterface $responseFactory,
        WebHttpHandlerConfigurationFindActionInterface $webHttpHandlerConfigurationFindAction,
        Psr7MessageFormatterContract $psr7MessageLogFormatter,
        HttpHandlerDumpDirectoryPathProviderInterface $httpHandlerDumpDirectoryPathProvider
    ) {
        $this->actor = $actor;
        $this->contextFactory = $contextFactory;
        $this->logger = $logger;
        $this->stackBuilderFactory = $stackBuilderFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->responseFactory = $responseFactory;
        $this->webHttpHandlerConfigurationFindAction = $webHttpHandlerConfigurationFindAction;
        $this->psr7MessageLogFormatter = $psr7MessageLogFormatter;
        $this->httpHandlerDumpDirectoryPathProvider = $httpHandlerDumpDirectoryPathProvider;
    }

    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface
    {
        // TODO push onto global logging context stack
        $correlationId = Uuid::uuid4()->toString();

        $portalNodeKey = $portalNodeKey->withoutAlias();
        $path = $request->getUri()->getPath();
        $response = $this->responseFactory->createResponse(501);
        $bridgeExpectsDumps = $request->getAttribute(self::REQUEST_ATTRIBUTE_DUMPS_EXPECTED, false);

        if ($bridgeExpectsDumps) {
            $originalRequest = $request->getAttribute(self::REQUEST_ATTRIBUTE_ORIGINAL_REQUEST);

            if (!$originalRequest instanceof ServerRequestInterface) {
                $originalRequest = null;
            }

            $logPath = $this->httpHandlerDumpDirectoryPathProvider->provide($portalNodeKey) . $correlationId . '.';

            if ($originalRequest instanceof ServerRequestInterface) {
                \file_put_contents(
                    $logPath . $this->psr7MessageLogFormatter->getFileExtension($originalRequest),
                    $this->psr7MessageLogFormatter->formatMessage($originalRequest)
                );
            }

            $response = $this->handlePortalNodeRequest($portalNodeKey, $path, $correlationId, $request, $response);

            \file_put_contents(
                $logPath . $this->psr7MessageLogFormatter->getFileExtension($response),
                $this->psr7MessageLogFormatter->formatMessage($response)
            );

            return $response;
        }

        return $this->handlePortalNodeRequest($portalNodeKey, $path, $correlationId, $request, $response);
    }

    private function handlePortalNodeRequest(
        PortalNodeKeyInterface $portalNodeKey,
        string $path,
        string $correlationId,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        foreach (\array_keys($request->getAttributes()) as $attributeKey) {
            if (\str_starts_with($attributeKey, self::REQUEST_ATTRIBUTE_PREFIX)) {
                $request = $request->withoutAttribute($attributeKey);
            }
        }

        $enabledCheck = $this->webHttpHandlerConfigurationFindAction->find(
            new WebHttpHandlerConfigurationFindCriteria($portalNodeKey, $path, 'enabled')
        );

        $enabled = (bool) ($enabledCheck->getValue()['value'] ?? true);

        if (!$enabled) {
            $this->logger->warning(LogMessage::WEB_HTTP_HANDLE_DISABLED(), [
                'code' => 1636845085,
                'path' => $path,
                'portalNodeKey' => $portalNodeKey,
                'request' => $request,
                'web_http_correlation_id' => $correlationId,
            ]);

            $response = $response->withStatus(423);
        } else {
            $stack = $this->getStack($portalNodeKey, $path);

            if (!$stack instanceof HttpHandlerStackInterface) {
                $this->logger->critical(LogMessage::WEB_HTTP_HANDLE_NO_HANDLER_FOR_PATH(), [
                    'code' => 1636845086,
                    'path' => $path,
                    'portalNodeKey' => $portalNodeKey,
                    'request' => $request,
                    'web_http_correlation_id' => $correlationId,
                ]);
            } else {
                $response = $this->actor->performHttpHandling($request, $response, $stack, $this->getContext($portalNodeKey));
            }
        }

        return $response->withHeader('X-HeptaConnect-Correlation-Id', $correlationId);
    }

    private function getStack(PortalNodeKeyInterface $portalNodeKey, string $path): ?HttpHandlerStackInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey) . $path;

        if (!\array_key_exists($cacheKey, $this->stackCache)) {
            $builder = $this->stackBuilderFactory
                ->createHttpHandlerStackBuilder($portalNodeKey, $path)
                ->pushSource()
                // TODO break when source is already empty
                ->pushDecorators();

            if (!$builder->isEmpty()) {
                $builder->push(new HttpMiddlewareChainHandler($path));
            }

            $this->stackCache[$cacheKey] = $builder->isEmpty() ? null : $builder->build();
        }

        $result = $this->stackCache[$cacheKey];

        if ($result instanceof HttpHandlerStackInterface) {
            return clone $result;
        }

        return null;
    }

    private function getContext(PortalNodeKeyInterface $portalNodeKey): HttpHandleContextInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey);
        $this->contextCache[$cacheKey] ??= $this->contextFactory->createContext($portalNodeKey);

        return clone $this->contextCache[$cacheKey];
    }
}
