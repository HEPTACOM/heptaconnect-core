<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;

class ReceiveContext extends AbstractPortalNodeContext implements ReceiveContextInterface
{
    private EntityStatusContract $entityStatus;

    private MappingServiceInterface $mappingService;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        EntityStatusContract $entityStatus,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        parent::__construct(
            $configurationService,
            $portalRegistry,
            $portalStorageFactory,
            $resourceLocking,
            $portalStackServiceContainerFactory,
            $portalNodeKey
        );
        $this->entityStatus = $entityStatus;
        $this->mappingService = $mappingService;
    }

    public function getEntityStatus(): EntityStatusContract
    {
        return $this->entityStatus;
    }

    public function markAsFailed(MappingNodeKeyInterface $mappingNodeKey, \Throwable $throwable): void
    {
        $this->mappingService->addException(
            $this->getPortalNodeKey(),
            $mappingNodeKey,
            $throwable
        );
    }
}
