<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class StatusReportingContext implements StatusReportingContextInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationServiceInterface $configurationService;

    private PortalStorageFactory $portalStorageFactory;

    private PortalNodeKeyInterface $portalNodeKey;

    private ResourceLockFacade $resourceLockFacade;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationServiceInterface $configurationService,
        PortalStorageFactory $portalStorageFactory,
        PortalNodeKeyInterface $portalNodeKey,
        ResourceLockFacade $resourceLockFacade
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->configurationService = $configurationService;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->portalNodeKey = $portalNodeKey;
        $this->resourceLockFacade = $resourceLockFacade;
    }

    public function getPortal(): PortalContract
    {
        return $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    public function getConfig(): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($this->portalNodeKey);
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($this->portalNodeKey);
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->resourceLockFacade;
    }
}
