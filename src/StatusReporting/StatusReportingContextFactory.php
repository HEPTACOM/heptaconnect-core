<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class StatusReportingContextFactory implements StatusReportingContextFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationServiceInterface $configurationService;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationServiceInterface $configurationService,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->configurationService = $configurationService;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): StatusReportingContextInterface
    {
        return new StatusReportingContext(
            $this->portalRegistry,
            $this->configurationService,
            $this->portalStorageFactory,
            $portalNodeKey,
            new ResourceLockFacade($this->resourceLocking)
        );
    }
}
