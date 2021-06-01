<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;

class EmitContextFactory
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
    }

    public function createContext(PortalNodeKeyInterface $portalNodeKey): EmitContextInterface
    {
        return new EmitContext(
            $this->configurationService,
            $this->portalRegistry,
            $this->portalStorageFactory,
            $this->resourceLocking,
            $this->portalStackServiceContainerFactory,
            $this->mappingService,
            $portalNodeKey,
            $this->mappingNodeRepository
        );
    }
}
