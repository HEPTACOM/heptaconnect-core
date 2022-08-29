<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleHttpHandlersFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
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

    private HttpHandlerStackProcessorInterface $stackProcessor;

    private HttpHandleContextFactoryInterface $contextFactory;

    private LoggerInterface $logger;

    private HttpHandlerStackBuilderFactoryInterface $stackBuilderFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ResponseFactoryInterface $responseFactory;

    private WebHttpHandlerConfigurationFindActionInterface $webHttpHandlerConfigurationFindAction;

    private HttpHandleHttpHandlersFactoryInterface $httpHandleHttpHandlersFactory;

    public function __construct(
        HttpHandlerStackProcessorInterface $stackProcessor,
        HttpHandleContextFactoryInterface $contextFactory,
        LoggerInterface $logger,
        HttpHandlerStackBuilderFactoryInterface $stackBuilderFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ResponseFactoryInterface $responseFactory,
        WebHttpHandlerConfigurationFindActionInterface $webHttpHandlerConfigurationFindAction,
        HttpHandleHttpHandlersFactoryInterface $httpHandleHttpHandlersFactory
    ) {
        $this->stackProcessor = $stackProcessor;
        $this->contextFactory = $contextFactory;
        $this->logger = $logger;
        $this->stackBuilderFactory = $stackBuilderFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->responseFactory = $responseFactory;
        $this->webHttpHandlerConfigurationFindAction = $webHttpHandlerConfigurationFindAction;
        $this->httpHandleHttpHandlersFactory = $httpHandleHttpHandlersFactory;
    }

    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface
    {
        $portalNodeKey = $portalNodeKey->withoutAlias();
        $path = $request->getUri()->getPath();
        $response = $this->responseFactory->createResponse(501);
        // TODO push onto global logging context stack
        $correlationId = Uuid::uuid4()->toString();

        $enabledCheck = $this->webHttpHandlerConfigurationFindAction->find(new WebHttpHandlerConfigurationFindCriteria($portalNodeKey, $path, 'enabled'));
        $enabled = (bool) ($enabledCheck->getValue()['value'] ?? true);

        if (!$enabled) {
            $this->logger->warning(LogMessage::WEB_HTTP_HANDLE_DISABLED(), [
                'code' => 1636845085,
                'path' => $path,
                'portalNodeKey' => $portalNodeKey,
                'request' => $request,
                'web_http_correlation_id' => $correlationId,
            ]);

            return $response->withHeader('X-HeptaConnect-Correlation-Id', $correlationId)->withStatus(423);
        }

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
            $response = $this->stackProcessor->processStack($request, $response, $stack, $this->getContext($portalNodeKey));
        }

        return $response->withHeader('X-HeptaConnect-Correlation-Id', $correlationId);
    }

    private function getStack(PortalNodeKeyInterface $portalNodeKey, string $path): ?HttpHandlerStackInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey) . $path;

        if (!\array_key_exists($cacheKey, $this->stackCache)) {
            $builder = $this->stackBuilderFactory
                ->createHttpHandlerStackBuilder($portalNodeKey, $path)
                ->pushSource();

            if ($builder->isEmpty()) {
                $this->stackCache[$cacheKey] = null;
            } else {
                $builder = $builder->pushDecorators();

                foreach ($this->httpHandleHttpHandlersFactory->createHttpHandlers($portalNodeKey, $path) as $handler) {
                    $builder = $builder->push($handler);
                }

                $this->stackCache[$cacheKey] = $builder->build();
            }
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
