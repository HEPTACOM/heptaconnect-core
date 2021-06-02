<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;

class EmitContext extends AbstractPortalNodeContext implements EmitContextInterface
{
    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        MappingServiceInterface $mappingService,
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        parent::__construct(
            $configurationService,
            $portalRegistry,
            $portalStorageFactory,
            $resourceLocking,
            $portalStackServiceContainerFactory,
            $portalNodeKey
        );
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
    }

    public function markAsFailed(string $externalId, string $datasetEntityClassName, \Throwable $throwable): void
    {
        $mappingNodeKeys = $this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalIds(
            $datasetEntityClassName,
            $this->getPortalNodeKey(),
            [$externalId]
        );

        /** @var MappingNodeKeyInterface $mappingNodeKey */
        foreach ($mappingNodeKeys as $mappingNodeKey) {
            $this->mappingService->addException(
                $this->getPortalNodeKey(),
                $mappingNodeKey,
                $throwable
            );
        }
    }
}
