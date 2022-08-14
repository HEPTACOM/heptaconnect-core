<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractPortalNodeContext implements PortalNodeContextInterface
{
    private ContainerInterface $container;

    private ?array $configuration;

    public function __construct(ContainerInterface $container, ?array $configuration)
    {
        $this->container = $container;
        $this->configuration = $configuration;
    }

    public function getConfig(): ?array
    {
        return $this->configuration;
    }

    public function getPortal(): PortalContract
    {
        return $this->getContainer()->get(PortalContract::class);
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->getContainer()->get(PortalNodeKeyInterface::class);
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->getContainer()->get(ResourceLockFacade::class);
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->getContainer()->get(PortalStorageInterface::class);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
