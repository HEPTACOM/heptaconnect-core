<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use DeepCopy\DeepCopy;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityTrackerContract;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class Router implements RouterInterface, MessageSubscriberInterface
{
    private DeepCopy $deepCopy;

    private EmitServiceInterface $emitService;

    private ReceiveServiceInterface $receiveService;

    private RouteRepositoryContract $routeRepository;

    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private DatasetEntityTrackerContract $datasetEntityTracker;

    private EntityMapperContract $entityMapper;

    private EntityReflectorContract $entityReflector;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private DeepObjectIteratorContract $objectIterator;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        RouteRepositoryContract $routeRepository,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository,
        DatasetEntityTrackerContract $datasetEntityTracker,
        EntityMapperContract $entityMapper,
        EntityReflectorContract $entityReflector,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        // TODO replace with deep clone from DI
        $this->deepCopy = new DeepCopy();
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->routeRepository = $routeRepository;
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->datasetEntityTracker = $datasetEntityTracker;
        $this->datasetEntityTracker->deny(PrimaryKeySharingMappingStruct::class);
        $this->entityMapper = $entityMapper;
        $this->entityReflector = $entityReflector;
        $this->storageKeyGenerator = $storageKeyGenerator;
        // TODO get from DI
        $this->objectIterator = new DeepObjectIteratorContract();
    }

    public static function getHandledMessages(): iterable
    {
        yield PublishMessage::class => ['method' => 'handlePublishMessage'];
        yield BatchPublishMessage::class => ['method' => 'handleBatchPublishMessage'];
        yield EmitMessage::class => ['method' => 'handleEmitMessage'];
    }

    public function handlePublishMessage(PublishMessage $message): void
    {
        $mapping = $message->getMapping();

        $this->emitService->emit(new TypedMappingCollection($mapping->getDatasetEntityClassName(), [$mapping]));
    }

    public function handleBatchPublishMessage(BatchPublishMessage $message): void
    {
        $typedMappingCollections = [];

        /** @var MappingInterface $mapping */
        foreach ($message->getMappings() as $mapping) {
            $typedMappingCollections[$mapping->getDatasetEntityClassName()][] = $mapping;
        }

        foreach ($typedMappingCollections as $type => $typedMappingCollection) {
            $this->emitService->emit(new TypedMappingCollection($type, $typedMappingCollection));
        }
    }

    public function handleEmitMessage(EmitMessage $message): void
    {
        $mappedDatasetEntityStruct = $message->getMappedDatasetEntityStruct();
        $mapping = $mappedDatasetEntityStruct->getMapping();
        $portalNodeKey = $mapping->getPortalNodeKey();

        $routeIds = $this->routeRepository->listBySourceAndEntityType(
            $portalNodeKey,
            $mapping->getDatasetEntityClassName()
        );

        $routeIds = iterable_to_array($routeIds);

        if (\count($routeIds) === 0) {
            $portalNodeId = $this->storageKeyGenerator->serialize($portalNodeKey);

            // TODO: add custom type for exception
            throw new \Exception(\sprintf(\implode(PHP_EOL, [
                'Message is not routed. Add a route and re-explore this entity.',
                'source portal: %s',
                'data type: %s',
                'external id: $s',
            ]), $portalNodeId, $mapping->getDatasetEntityClassName(), $mapping->getExternalId()));
        }

        $trackedEntities = new TrackedEntityCollection($this->objectIterator->iterate($mappedDatasetEntityStruct->getDatasetEntity()));
        $mappingsToEnsure = new MappingComponentCollection();

        /** @var DatasetEntityInterface $trackedEntity */
        foreach ($trackedEntities->getIterator() as $trackedEntity) {
            if (!$trackedEntity instanceof DatasetEntityInterface || $trackedEntity->getPrimaryKey() === null) {
                continue;
            }

            $mappingsToEnsure->push([new MappingComponentStruct(
                $portalNodeKey,
                \get_class($trackedEntity),
                $trackedEntity->getPrimaryKey()
            )]);
        }

        $this->mappingService->ensurePersistence($mappingsToEnsure);

        $trackedEntities = $this->entityMapper->mapEntities(
            $trackedEntities,
            $portalNodeKey
        );

        $typedMappedDatasetEntityCollection = new TypedMappedDatasetEntityCollection($mapping->getDatasetEntityClassName());
        $receivedEntityData = [];

        foreach ($routeIds as $routeId) {
            $route = $this->routeRepository->read($routeId);
            $targetMapping = $this->mappingService->reflect($this->mappingService->get(
                $mapping->getDatasetEntityClassName(),
                $mapping->getPortalNodeKey(),
                $mapping->getExternalId()
            ), $route->getTargetKey());

            $this->entityReflector->reflectEntities($trackedEntities, $route->getTargetKey());
            $this->datasetEntityTracker->listen();
            $datasetEntity = $this->deepCopy->copy($mappedDatasetEntityStruct->getDatasetEntity());
            $receivedEntityData[] = $this->datasetEntityTracker->retrieve()->filter(
                fn (DatasetEntityInterface $entity) => !$entity instanceof PrimaryKeySharingMappingStruct
            );

            /** @var MappedDatasetEntityStruct $trackedEntity */
            foreach ($trackedEntities as $trackedEntity) {
                $trackedEntity->getDatasetEntity()->unattach(PrimaryKeySharingMappingStruct::class);
            }

            $typedMappedDatasetEntityCollection->push([
                new MappedDatasetEntityStruct($targetMapping, $datasetEntity),
            ]);
        }

        foreach ($this->objectIterator->iterate($typedMappedDatasetEntityCollection) as $object) {
            if (!$object instanceof DatasetEntityInterface) {
                continue;
            }

            $attachable = new PrimaryKeyChangesAttachable(\get_class($object));
            $attachable->setForeignKey($object->getPrimaryKey());
            $object->attach($attachable);
        }

        $this->receiveService->receive(
            $typedMappedDatasetEntityCollection,
            function (PortalNodeKeyInterface $targetPortalNodeKey) use ($receivedEntityData) {
                $exceptions = [];
                $originalReflectionMappingsByType = [];
                $keyChangesByType = [];

                foreach ($receivedEntityData as $receivedEntities) {
                    foreach ($receivedEntities as $receivedEntity) {
                        if (!$receivedEntity instanceof DatasetEntityInterface
                            || $receivedEntity->getPrimaryKey() === null) {
                            continue;
                        }

                        $receivedEntityType = \get_class($receivedEntity);
                        $primaryKeyChanges = $receivedEntity->getAttachment(PrimaryKeyChangesAttachable::class);

                        if ($primaryKeyChanges instanceof PrimaryKeyChangesAttachable
                            && !\is_null($primaryKeyChanges->getFirstForeignKey())
                            && !\is_null($primaryKeyChanges->getForeignKey())
                            && $primaryKeyChanges->getFirstForeignKey() !== $primaryKeyChanges->getForeignKey()) {
                            $keyChangesByType[$receivedEntityType][$primaryKeyChanges->getFirstForeignKey()] = $primaryKeyChanges->getForeignKey();
                        }

                        $original = $receivedEntity->getAttachment(PrimaryKeySharingMappingStruct::class);

                        if (!$original instanceof PrimaryKeySharingMappingStruct || $original->getExternalId() === null) {
                            continue;
                        }

                        $originalReflectionMappingsByType[$receivedEntityType][$receivedEntity->getPrimaryKey()] = $original;
                    }
                }

                // TODO log these uncommon cases
                foreach ($keyChangesByType as $datasetEntityType => $keyChanges) {
                    $oldMatchesIterable = $this->mappingService->getListByExternalIds(
                        $datasetEntityType,
                        $targetPortalNodeKey,
                        \array_keys($keyChanges)
                    );

                    foreach ($oldMatchesIterable as $oldKey => $mapping) {
                        $mapping->setExternalId($keyChanges[$oldKey]);
                        $this->mappingService->save($mapping);
                    }
                }

                foreach ($originalReflectionMappingsByType as $datasetEntityType => $originalReflectionMappings) {
                    $externalIds = \array_map('strval', \array_keys($originalReflectionMappings));
                    $receivedMappingsIterable = $this->mappingService->getListByExternalIds(
                        $datasetEntityType,
                        $targetPortalNodeKey,
                        $externalIds
                    );

                    foreach ($receivedMappingsIterable as $externalId => $receivedMapping) {
                        $original = $originalReflectionMappings[$externalId];

                        if ($receivedMapping->getMappingNodeKey()->equals($original->getMappingNodeKey())) {
                            continue;
                        }

                        try {
                            $this->mappingService->merge(
                                $receivedMapping->getMappingNodeKey(),
                                $original->getMappingNodeKey()
                            );
                        } catch (\Throwable $exception) {
                            $exceptions[] = $exception;
                        }
                    }
                }

                if ($exceptions) {
                    throw new CumulativeMappingException('Errors occured while merging mapping nodes.', ...$exceptions);
                }
            }
        );
    }
}
