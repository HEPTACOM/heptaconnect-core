<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

abstract class AbstractPortalNodeContext implements PortalNodeContextInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockFacade $resourceLockFacade;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLockFacade = new ResourceLockFacade($resourceLocking);
        $this->portalNodeKey = $portalNodeKey;
    }

    public function getConfig(): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($this->portalNodeKey);
    }

    public function getPortal(): PortalContract
    {
        return $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeKey;
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->resourceLockFacade;
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($this->portalNodeKey);
    }
}
