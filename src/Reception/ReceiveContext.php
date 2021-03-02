<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractStatelessContext;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;

class ReceiveContext extends AbstractStatelessContext implements ReceiveContextInterface
{
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
        parent::__construct(
            $mappingService,
            $configurationService,
            $portalRegistry,
            $portalStorageFactory,
            $resourceLocking,
            $portalStackServiceContainerFactory
        );
        $this->entityStatus = $entityStatus;
    }

    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }
}
