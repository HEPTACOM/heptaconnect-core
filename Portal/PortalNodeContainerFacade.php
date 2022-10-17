<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeContainerFacadeContract;
use Heptacom\HeptaConnect\Core\Support\HttpMiddlewareCollector;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Psr\Container\ContainerInterface;

final class PortalNodeContainerFacade extends PortalNodeContainerFacadeContract
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPortal(): PortalContract
    {
        return $this->get(PortalContract::class);
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->get(PortalNodeKeyInterface::class);
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->get(ResourceLockFacade::class);
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->get(PortalStorageInterface::class);
    }

    public function getWebHttpClient(): HttpClientContract
    {
        return $this->get(HttpClientContract::class);
    }

    public function getFlowComponentRegistry(): FlowComponentRegistry
    {
        return $this->get(FlowComponentRegistry::class);
    }

    public function getHttpHandlerMiddlewareCollector(): HttpMiddlewareCollector
    {
        return $this->get(HttpMiddlewareCollector::class);
    }

    /**
     * @template TGet of object
     *
     * @param class-string<TGet>|string $id
     *
     * @return ($id is class-string<TGet> ? TGet : string)
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    public function has($id)
    {
        return $this->container->has($id);
    }
}
