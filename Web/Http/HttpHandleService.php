<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleFlowHttpHandlersFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract\ServerRequestCycleDumpCheckerInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract\ServerRequestCycleDumperInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\ServerRequestCycle;
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

    public function __construct(
        private HttpHandlerStackProcessorInterface $stackProcessor,
        private HttpHandleContextFactoryInterface $contextFactory,
        private LoggerInterface $logger,
        private HttpHandlerStackBuilderFactoryInterface $stackBuilderFactory,
        private StorageKeyGeneratorContract $storageKeyGenerator,
        private ResponseFactoryInterface $responseFactory,
        private WebHttpHandlerConfigurationFindActionInterface $webHttpHandlerConfigurationFindAction,
        private HttpHandleFlowHttpHandlersFactoryInterface $httpHandleFlowHttpHandlersFactory,
        private ServerRequestCycleDumpCheckerInterface $dumpChecker,
        private ServerRequestCycleDumperInterface $requestResponsePairDumper
    ) {
    }

    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface
    {
        $httpHandlerStackIdentifier = new HttpHandlerStackIdentifier(
            $portalNodeKey->withoutAlias(),
            $request->getUri()->getPath()
        );
        $response = $this->responseFactory->createResponse(501);
        $response = $this->handlePortalNodeRequest($httpHandlerStackIdentifier, $request, $response);
        $requestCycle = new ServerRequestCycle($request, $response);

        if ($this->dumpChecker->shallDump($httpHandlerStackIdentifier, $requestCycle)) {
            $this->requestResponsePairDumper->dump($httpHandlerStackIdentifier, $requestCycle);
        }

        return $response;
    }

    private function handlePortalNodeRequest(
        HttpHandlerStackIdentifier $stackIdentifier,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // TODO push onto global logging context stack
        $correlationId = Uuid::uuid4()->toString();

        foreach (\array_keys($request->getAttributes()) as $attributeKey) {
            if (\str_starts_with($attributeKey, self::REQUEST_ATTRIBUTE_PREFIX)) {
                $request = $request->withoutAttribute($attributeKey);
            }
        }

        $enabledCheck = $this->webHttpHandlerConfigurationFindAction->find(new WebHttpHandlerConfigurationFindCriteria(
            $stackIdentifier->getPortalNodeKey(),
            $stackIdentifier->getPath(),
            'enabled'
        ));

        $enabled = (bool) ($enabledCheck->getValue()['value'] ?? true);

        if (!$enabled) {
            $this->logger->warning(LogMessage::WEB_HTTP_HANDLE_DISABLED(), [
                'code' => 1636845085,
                'path' => $stackIdentifier->getPath(),
                'portalNodeKey' => $stackIdentifier->getPortalNodeKey(),
                'request' => $request,
                'web_http_correlation_id' => $correlationId,
            ]);

            $response = $response->withStatus(423);
        } else {
            $stack = $this->getStack($stackIdentifier);

            if (!$stack instanceof HttpHandlerStackInterface) {
                $this->logger->critical(LogMessage::WEB_HTTP_HANDLE_NO_HANDLER_FOR_PATH(), [
                    'code' => 1636845086,
                    'path' => $stackIdentifier->getPath(),
                    'portalNodeKey' => $stackIdentifier->getPortalNodeKey(),
                    'request' => $request,
                    'web_http_correlation_id' => $correlationId,
                ]);
            } else {
                $response = $this->stackProcessor->processStack($request, $response, $stack, $this->getContext($stackIdentifier->getPortalNodeKey()));
            }
        }

        return $response->withHeader('X-HeptaConnect-Correlation-Id', $correlationId);
    }

    private function getStack(HttpHandlerStackIdentifier $identifier): ?HttpHandlerStackInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($identifier->getPortalNodeKey()->withoutAlias()) . $identifier->getPath();

        if (!\array_key_exists($cacheKey, $this->stackCache)) {
            $builder = $this->stackBuilderFactory
                ->createHttpHandlerStackBuilder($identifier->getPortalNodeKey(), $identifier->getPath())
                ->pushSource();

            if ($builder->isEmpty()) {
                $this->stackCache[$cacheKey] = null;
            } else {
                $builder = $builder->pushDecorators();

                foreach ($this->httpHandleFlowHttpHandlersFactory->createHttpHandlers($identifier->getPortalNodeKey(), $identifier->getPath()) as $handler) {
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
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());
        $this->contextCache[$cacheKey] ??= $this->contextFactory->createContext($portalNodeKey);

        return clone $this->contextCache[$cacheKey];
    }
}
