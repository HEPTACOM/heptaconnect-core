<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeContainerFacadeContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractPortalNodeContext implements PortalNodeContextInterface
{
    public function __construct(
        private PortalNodeContainerFacadeContract $containerFacade,
        private ?array $configuration
    ) {
    }

    public function getConfig(): ?array
    {
        return $this->configuration;
    }

    public function getPortal(): PortalContract
    {
        return $this->containerFacade->getPortal();
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->containerFacade->getPortalNodeKey();
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->containerFacade->getResourceLocker();
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->containerFacade->getStorage();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->containerFacade->getLogger();
    }

    public function getContainer(): ContainerInterface
    {
        return $this->containerFacade;
    }
}
