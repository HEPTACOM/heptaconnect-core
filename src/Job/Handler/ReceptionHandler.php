<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Job\Contract\ReceptionHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Symfony\Component\Lock\LockFactory;

class ReceptionHandler implements ReceptionHandlerInterface
{
    private RouteRepositoryContract $routeRepository;

    private LockFactory $lockFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityReflectorContract $entityReflector;

    private EntityMapperContract $entityMapper;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private ReceiveServiceInterface $receiveService;

    private DeepObjectIteratorContract $objectIterator;

    private JobRepositoryContract $jobRepository;

    public function __construct(
        RouteRepositoryContract $routeRepository,
        LockFactory $lockFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityReflectorContract $entityReflector,
        EntityMapperContract $entityMapper,
        MappingNodeRepositoryContract $mappingNodeRepository,
        ReceiveServiceInterface $receiveService,
        DeepObjectIteratorContract $objectIterator,
        JobRepositoryContract $jobRepository
    ) {
        $this->routeRepository = $routeRepository;
        $this->lockFactory = $lockFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityReflector = $entityReflector;
        $this->entityMapper = $entityMapper;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->receiveService = $receiveService;
        $this->objectIterator = $objectIterator;
        $this->jobRepository = $jobRepository;
    }

    public function triggerReception(JobDataCollection $jobs): void
    {
        $receptions = [];

        /** @var JobData $job */
        foreach ($jobs as $job) {
            $routeKey = $job->getPayload()[Reception::ROUTE_KEY] ?? null;

            if (!$routeKey instanceof RouteKeyInterface) {
                // TODO error
                continue;
            }

            $entity = $job->getPayload()[Reception::ENTITY] ?? null;

            if (!$entity instanceof DatasetEntityContract) {
                // TODO error
                continue;
            }

            $route = $this->routeRepository->read($routeKey);

            if ($route->getEntityType() !== \get_class($entity)) {
                // TODO error
                continue;
            }

            $externalId = $job->getMappingComponent()->getExternalId();

            if (!\is_string($externalId)) {
                // TODO error
                continue;
            }

            $targetPortal = $this->storageKeyGenerator->serialize($route->getTargetKey());
            $sourcePortal = $this->storageKeyGenerator->serialize($route->getSourceKey());

            $receptions[$route->getEntityType()][$targetPortal][$sourcePortal][$externalId] = [
                'mapping' => $job->getMappingComponent(),
                'entity' => $entity,
                'jobKey' => $job->getJobKey(),
            ];
        }

        $lockedReceptions = [];
        $locks = [];

        try {
            foreach ($receptions as $dataType => $portaledEntities) {
                foreach ($portaledEntities as $targetPortalKey => $sourcePortaledEntities) {
                    foreach ($sourcePortaledEntities as $sourcePortalKey => $entities) {
                        foreach ($entities as $externalId => $entity) {
                            $lock = $this->lockFactory->createLock('ca9137ba5ec646078043b96030a00e70_'.\md5(\join('_', [
                                    $sourcePortalKey,
                                    $targetPortalKey,
                                    $dataType,
                                    $externalId,
                                ])));

                            if (!$lock->acquire()) {
                                continue;
                            }

                            $locks[] = $lock;
                            $lockedReceptions[$dataType][$targetPortalKey][$sourcePortalKey][$externalId] = $entity;
                        }
                    }
                }
            }

            foreach ($lockedReceptions as $dataType => $portaledEntities) {
                foreach ($portaledEntities as $targetPortalKey => $sourcePortaledEntities) {
                    foreach ($sourcePortaledEntities as $sourcePortalKey => $entities) {
                        $sourcePortalNodeKey = $this->storageKeyGenerator->deserialize($sourcePortalKey);

                        if (!$sourcePortalNodeKey instanceof PortalNodeKeyInterface) {
                            continue;
                        }

                        $targetPortalNodeKey = $this->storageKeyGenerator->deserialize($targetPortalKey);

                        if (!$targetPortalNodeKey instanceof PortalNodeKeyInterface) {
                            continue;
                        }

                        // TODO inspect memory raise
                        $mappedEntities = $this->entityMapper->mapEntities(
                            new DatasetEntityCollection($this->objectIterator->iterate(\array_column($entities, 'entity'))),
                            $sourcePortalNodeKey
                        );
                        // TODO: improve performance
                        $this->entityReflector->reflectEntities($mappedEntities, $targetPortalNodeKey);

                        $externalIds = \array_map(
                            static fn (MappingComponentStructContract $m): ?string => $m->getExternalId(),
                            \array_column($entities, 'mapping')
                        );
                        $mappingNodeKeys = \iterable_to_array($this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalIds(
                            $dataType,
                            $sourcePortalNodeKey,
                            $externalIds,
                        ));

                        $mappedDatasetEntities = new TypedMappedDatasetEntityCollection($dataType);

                        foreach ($mappingNodeKeys as $externalId => $mappingNodeKey) {
                            // TODO: evaluate whether this is still required
                            $targetMapping = (new MappingStruct($targetPortalNodeKey, new MappingNodeStruct(
                                $mappingNodeKey,
                                $dataType
                            )))->setExternalId($externalId);

                            $mappedDatasetEntities->push([new MappedDatasetEntityStruct($targetMapping, $entities[$externalId]['entity'])]);
                        }

                        $now = new \DateTimeImmutable();

                        foreach (\array_keys($mappingNodeKeys) as $externalId) {
                            $this->jobRepository->start($entities[$externalId]['jobKey'], $now);
                        }

                        $this->receiveService->receive($mappedDatasetEntities);

                        $now = new \DateTimeImmutable();

                        foreach (\array_keys($mappingNodeKeys) as $externalId) {
                            $this->jobRepository->finish($entities[$externalId]['jobKey'], $now);
                        }
                    }
                }
            }
        } finally {
            foreach ($locks as $lock) {
                if ($lock->isAcquired()) {
                    $lock->release();
                }
            }
        }
    }
}
