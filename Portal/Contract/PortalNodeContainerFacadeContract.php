<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\FlowComponentRegistry;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Psr\Container\ContainerInterface;

/**
 * Facade around a portal node container to normalize accessing inner services.
 */
abstract class PortalNodeContainerFacadeContract implements ContainerInterface
{
    /**
     * Gets the portal of the container.
     */
    abstract public function getPortal(): PortalContract;

    /**
     * Gets the portal node key of the container.
     */
    abstract public function getPortalNodeKey(): PortalNodeKeyInterface;

    /**
     * Gets the resource locker component.
     */
    abstract public function getResourceLocker(): ResourceLockFacade;

    /**
     * Gets the storage/cache implementation.
     */
    abstract public function getStorage(): PortalStorageInterface;

    /**
     * Gets the HTTP client
     */
    abstract public function getWebHttpClient(): HttpClientContract;

    /**
     * Gets the flow component registry.
     */
    abstract public function getFlowComponentRegistry(): FlowComponentRegistry;
}
