<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Job\Contract\ReceptionHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\Exception\ReceptionJobHandlingException;
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
use Heptacom\HeptaConnect\Portal\Base\StorageKey\RouteKeyCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Finish\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Start\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Start\JobStartPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

class ReceptionHandler implements ReceptionHandlerInterface
{
    private LockFactory $lockFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityReflectorContract $entityReflector;

    private EntityMapperContract $entityMapper;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private ReceiveServiceInterface $receiveService;

    private DeepObjectIteratorContract $objectIterator;

    private RouteGetActionInterface $routeGetAction;

    private LoggerInterface $logger;

    private JobStartActionInterface $jobStartAction;

    private JobFinishActionInterface $jobFinishAction;

    public function __construct(
        LockFactory $lockFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityReflectorContract $entityReflector,
        EntityMapperContract $entityMapper,
        MappingNodeRepositoryContract $mappingNodeRepository,
        ReceiveServiceInterface $receiveService,
        DeepObjectIteratorContract $objectIterator,
        RouteGetActionInterface $routeGetAction,
        LoggerInterface $logger,
        JobStartActionInterface $jobStartAction,
        JobFinishActionInterface $jobFinishAction
    ) {
        $this->lockFactory = $lockFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityReflector = $entityReflector;
        $this->entityMapper = $entityMapper;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->receiveService = $receiveService;
        $this->objectIterator = $objectIterator;
        $this->routeGetAction = $routeGetAction;
        $this->logger = $logger;
        $this->jobStartAction = $jobStartAction;
        $this->jobFinishAction = $jobFinishAction;
    }

    public function triggerReception(JobDataCollection $jobs): void
    {
        $receptions = [];
        $routeKeys = new RouteKeyCollection(\iterable_map(
            $jobs->column('getPayload'),
            static fn (?array $p): ?RouteKeyInterface => $p[Reception::ROUTE_KEY] ?? null
        ));
        $routeDatas = $this->routeGetAction->get(new RouteGetCriteria($routeKeys));
        /** @var RouteGetResult[] $routes */
        $routes = [];

        foreach ($routeDatas as $routeData) {
            $routes[$this->storageKeyGenerator->serialize($routeData->getRouteKey())] = $routeData;
        }

        /** @var JobData $job */
        foreach ($jobs as $job) {
            try {
                $routeKey = $job->getPayload()[Reception::ROUTE_KEY] ?? null;

                if (!$routeKey instanceof RouteKeyInterface) {
                    throw new ReceptionJobHandlingException($job, 1636503503);
                }

                $entity = $job->getPayload()[Reception::ENTITY] ?? null;

                if (!$entity instanceof DatasetEntityContract) {
                    throw new ReceptionJobHandlingException($job, 1636503504);
                }

                $route = $routes[$this->storageKeyGenerator->serialize($routeKey)] ?? null;

                if (!$route instanceof RouteGetResult) {
                    throw new ReceptionJobHandlingException($job, 1636503505);
                }

                if (!\in_array(RouteCapability::RECEPTION, $route->getCapabilities(), true)) {
                    throw new ReceptionJobHandlingException($job, 1636503506);
                }

                if ($route->getEntityType() !== \get_class($entity)) {
                    throw new ReceptionJobHandlingException($job, 1636503507);
                }

                $externalId = $job->getMappingComponent()->getExternalId();

                if ($externalId !== $entity->getPrimaryKey()) {
                    throw new ReceptionJobHandlingException($job, 1636503508);
                }

                $targetPortal = $this->storageKeyGenerator->serialize($route->getTargetPortalNodeKey());
                $sourcePortal = $this->storageKeyGenerator->serialize($route->getSourcePortalNodeKey());
            } catch (ReceptionJobHandlingException|UnsupportedStorageKeyException $throwable) {
                $this->logger->critical('Reception job preparation failed', [
                    'code' => $throwable->getCode(),
                    'exception' => $throwable,
                ]);

                continue;
            }

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
                            $lock = $this->lockFactory->createLock('ca9137ba5ec646078043b96030a00e70_' . \md5(\implode('_', [
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

                        /** @var DatasetEntityContract[] $rawEntities */
                        $rawEntities = \array_column($entities, 'entity');
                        /** @var array<DatasetEntityContract|object> $rawEntities */
                        $rawEntities = $this->objectIterator->iterate($rawEntities);
                        /* @phpstan-ignore-next-line intended array of objects as collection will filter unwanted values */
                        $filteredEntityObjects = new DatasetEntityCollection($rawEntities);
                        // TODO inspect memory raise
                        $mappedEntities = $this->entityMapper->mapEntities($filteredEntityObjects, $sourcePortalNodeKey);
                        // TODO: improve performance
                        $this->entityReflector->reflectEntities($mappedEntities, $targetPortalNodeKey);

                        $externalIds = \array_map(
                            static fn (MappingComponentStructContract $m): string => $m->getExternalId(),
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
                            )))->setExternalId((string) $externalId);

                            $mappedDatasetEntities->push([new MappedDatasetEntityStruct($targetMapping, $entities[$externalId]['entity'])]);
                        }

                        $jobKeys = new JobKeyCollection();

                        foreach (\array_keys($mappingNodeKeys) as $externalId) {
                            $jobKeys->push([$entities[$externalId]['jobKey']]);
                        }

                        $this->jobStartAction->start(new JobStartPayload($jobKeys, new \DateTimeImmutable(), null));
                        $this->receiveService->receive($mappedDatasetEntities);
                        $this->jobFinishAction->finish(new JobFinishPayload($jobKeys, new \DateTimeImmutable(), null));
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
