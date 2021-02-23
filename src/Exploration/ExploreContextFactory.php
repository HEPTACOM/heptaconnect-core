<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContextFactory implements ExploreContextFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationServiceInterface $configurationService;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationServiceInterface $configurationService,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->configurationService = $configurationService;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface
    {
        return new ExploreContext(
            $this->configurationService,
            $this->portalRegistry,
            $this->portalStorageFactory,
            $this->resourceLocking,
            $this->portalStackServiceContainerFactory,
            $portalNodeKey
        );
    }
}
