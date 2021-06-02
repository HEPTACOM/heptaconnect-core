<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;

class ReceiveContextFactory
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private EntityStatusContract $entityStatus;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        EntityStatusContract $entityStatus
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->entityStatus = $entityStatus;
    }

    public function createContext(PortalNodeKeyInterface $portalNodeKey): ReceiveContextInterface
    {
        return new ReceiveContext(
            $this->mappingService,
            $this->configurationService,
            $this->portalRegistry,
            $this->portalStorageFactory,
            $this->resourceLocking,
            $this->portalStackServiceContainerFactory,
            $this->entityStatus,
            $portalNodeKey
        );
    }
}
