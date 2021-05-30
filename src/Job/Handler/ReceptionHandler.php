<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Symfony\Component\Lock\LockFactory;

class ReceptionHandler
{
    private RouteRepositoryContract $routeRepository;

    private LockFactory $lockFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityReflectorContract $entityReflector;

    private EntityMapperContract $entityMapper;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private ReceiveServiceInterface $receiveService;

    private DeepObjectIteratorContract $objectIterator;

    public function __construct(
        RouteRepositoryContract $routeRepository,
        LockFactory $lockFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityReflectorContract $entityReflector,
        EntityMapperContract $entityMapper,
        MappingNodeRepositoryContract $mappingNodeRepository,
        ReceiveServiceInterface $receiveService,
        DeepObjectIteratorContract $objectIterator
    ) {
        $this->routeRepository = $routeRepository;
        $this->lockFactory = $lockFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityReflector = $entityReflector;
        $this->entityMapper = $entityMapper;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->receiveService = $receiveService;
        $this->objectIterator = $objectIterator;
    }

    public function triggerReception(MappingComponentStructContract $mapping, array $payload): bool
    {
        $routeKey = $payload[Reception::ROUTE_KEY] ?? null;

        if (!$routeKey instanceof RouteKeyInterface) {
            // TODO error
            return false;
        }

        $entity = $payload[Reception::ENTITY] ?? null;

        if (!$entity instanceof DatasetEntityContract) {
            // TODO error
            return false;
        }

        $route = $this->routeRepository->read($routeKey);

        if ($route->getEntityClassName() !== \get_class($entity)) {
            // TODO error
            return true;
        }

        $mappingNodeKeys = \iterable_to_array($this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalIds(
            $mapping->getDatasetEntityClassName(),
            $mapping->getPortalNodeKey(),
            [$mapping->getExternalId()],
        ));
        $mappingNodeKey = \current($mappingNodeKeys);

        $lock = $this->lockFactory->createLock('ca9137ba5ec646078043b96030a00e70_'.\join('_', [
                $this->storageKeyGenerator->serialize($route->getSourceKey()),
                $this->storageKeyGenerator->serialize($route->getTargetKey()),
                $this->storageKeyGenerator->serialize($mappingNodeKey),
            ]));

        if (!$lock->acquire()) {
            return false;
        }

        $trackedEntities = new TrackedEntityCollection($this->objectIterator->iterate($entity));
        $mappingsToEnsure = new MappingComponentCollection();

        /** @var DatasetEntityContract $trackedEntity */
        foreach ($trackedEntities->getIterator() as $trackedEntity) {
            if (!$trackedEntity instanceof DatasetEntityContract || $trackedEntity->getPrimaryKey() === null) {
                continue;
            }

            $mappingsToEnsure->push([new MappingComponentStruct(
                $mapping->getPortalNodeKey(),
                \get_class($trackedEntity),
                $trackedEntity->getPrimaryKey()
            )]);
        }

        $trackedEntities = $this->entityMapper->mapEntities($trackedEntities, $mapping->getPortalNodeKey());
        $typedMappedDatasetEntities = new TypedMappedDatasetEntityCollection($mapping->getDatasetEntityClassName());

        try {
            // TODO: improve performance
            $this->entityReflector->reflectEntities($trackedEntities, $route->getTargetKey());

            $targetMapping = (new MappingStruct($route->getTargetKey(), new MappingNodeStruct(
                $mappingNodeKey,
                $mapping->getDatasetEntityClassName()
            )))->setExternalId($entity->getPrimaryKey());

            $typedMappedDatasetEntities->push([
                new MappedDatasetEntityStruct($targetMapping, $entity),
            ]);
            $this->receiveService->receive($typedMappedDatasetEntities);

            return true;
        } finally {
            $lock->release();
        }
    }
}
