<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;

class CronjobContextFactory
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function createContext(CronjobInterface $cronjob): CronjobContextInterface
    {
        return new CronjobContext(
            $this->configurationService,
            $this->portalRegistry,
            $this->portalStorageFactory,
            $this->resourceLocking,
            $this->portalStackServiceContainerFactory,
            $cronjob->getPortalNodeKey(),
            $cronjob
        );
    }
}
