<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeContainerFacadeContract;
use Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotFoundException;
use Heptacom\HeptaConnect\Core\Support\HttpMiddlewareCollector;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class PortalNodeContainerFacade extends PortalNodeContainerFacadeContract
{
    /**
     * @throws ServiceNotFoundException
     */
    public function __construct(
        private readonly ContainerInterface $container
    ) {
        foreach ((new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === 'get' || !\str_starts_with($method->getName(), 'get')) {
                continue;
            }

            $returnType = $method->getReturnType();

            if (!$returnType instanceof \ReflectionNamedType) {
                continue;
            }

            try {
                $method->invoke($this);
            } catch (\Throwable $callException) {
                throw new ServiceNotFoundException($returnType->getName(), 1666461305, $callException);
            }
        }
    }

    #[\Override]
    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->get(PortalNodeKeyInterface::class);
    }

    #[\Override]
    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->get(ResourceLockFacade::class);
    }

    #[\Override]
    public function getStorage(): PortalStorageInterface
    {
        return $this->get(PortalStorageInterface::class);
    }

    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->get(LoggerInterface::class);
    }

    #[\Override]
    public function getWebHttpClient(): HttpClientContract
    {
        return $this->get(HttpClientContract::class);
    }

    #[\Override]
    public function getFlowComponentRegistry(): FlowComponentRegistry
    {
        return $this->get(FlowComponentRegistry::class);
    }

    #[\Override]
    public function getHttpHandlerMiddlewareCollector(): HttpMiddlewareCollector
    {
        return $this->get(HttpMiddlewareCollector::class);
    }

    /**
     * @template TGet of object
     *
     * @param class-string<TGet>|string $id
     *
     * @return ($id is class-string<TGet> ? TGet : object|null)
     */
    #[\Override]
    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    #[\Override]
    public function has($id): bool
    {
        return $this->container->has($id);
    }
}
