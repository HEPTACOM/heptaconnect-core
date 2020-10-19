<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use DeepCopy\DeepCopy;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Mapping\Support\ReflectionMapping;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntity;
use Heptacom\HeptaConnect\Dataset\Base\Support\DatasetEntityTracker;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class Router implements RouterInterface, MessageSubscriberInterface
{
    private DeepCopy $deepCopy;

    private EmitServiceInterface $emitService;

    private ReceiveServiceInterface $receiveService;

    private RouteRepositoryContract $routeRepository;

    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        RouteRepositoryContract $routeRepository,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->deepCopy = new DeepCopy();
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->routeRepository = $routeRepository;
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
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

        $trackedEntities = $this->getTrackedEntities($portalNodeKey, $message->getTrackedEntities());
        $typedMappedDatasetEntityCollections = [];
        $receivedEntityData = [];

        foreach ($routeIds as $routeId) {
            $route = $this->routeRepository->read($routeId);
            $targetMapping = $this->mappingService->reflect($mapping, $route->getTargetKey());
            $entityClassName = $targetMapping->getDatasetEntityClassName();

            $typedMappedDatasetEntityCollections[$entityClassName] ??= new TypedMappedDatasetEntityCollection($entityClassName);

            $this->reflectTrackedEntities($trackedEntities, $route->getTargetKey());
            DatasetEntityTracker::listen();
            $datasetEntity = $this->deepCopy->copy($mappedDatasetEntityStruct->getDatasetEntity());
            $receivedEntityData[$entityClassName][] = DatasetEntityTracker::retrieve()->filter(
                fn (DatasetEntityInterface $entity) => !$entity instanceof ReflectionMapping
            );

            /** @var MappedDatasetEntityStruct $trackedEntity */
            foreach ($trackedEntities as $trackedEntity) {
                $trackedEntity->getDatasetEntity()->unattach(ReflectionMapping::class);
            }

            $typedMappedDatasetEntityCollections[$entityClassName]->push([
                new MappedDatasetEntityStruct($targetMapping, $datasetEntity),
            ]);
        }

        foreach ($typedMappedDatasetEntityCollections as $entityClassName => $typedMappedDatasetEntityCollection) {
            $receivedDataCollection = $receivedEntityData[$entityClassName];

            $this->receiveService->receive(
                $typedMappedDatasetEntityCollection,
                function (PortalNodeKeyInterface $targetPortalNodeKey) use ($receivedDataCollection) {
                    foreach ($receivedDataCollection as $receivedEntities) {
                        foreach ($receivedEntities as $receivedEntity) {
                            if (!$receivedEntity instanceof DatasetEntityInterface
                                || $receivedEntity->getPrimaryKey() === null) {
                                continue;
                            }

                            $original = $receivedEntity->getAttachment(ReflectionMapping::class);

                            if (!$original instanceof ReflectionMapping) {
                                continue;
                            }

                            $receivedMapping = $this->mappingService->get(
                                \get_class($receivedEntity),
                                $targetPortalNodeKey,
                                $receivedEntity->getPrimaryKey()
                            );

                            if ($receivedMapping->getMappingNodeKey()->equals($original->getMappingNodeKey())) {
                                continue;
                            }

                            $this->mappingService->merge(
                                $receivedMapping->getMappingNodeKey(),
                                $original->getMappingNodeKey()
                            );
                        }
                    }
                }
            );
        }
    }

    private function getTrackedEntities(
        PortalNodeKeyInterface $portalNodeKey,
        TrackedEntityCollection $trackedEntities
    ): MappedDatasetEntityCollection {
        $mappedDatasetEntities = \array_map(function (DatasetEntity $entity) use ($portalNodeKey): MappedDatasetEntityStruct {
            $dataType = \get_class($entity);
            $primaryKey = $entity->getPrimaryKey();

            if ($primaryKey !== null) {
                $mappingNodeKey = [...$this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalId($dataType, $portalNodeKey, $primaryKey)][0] ?? null;
            }

            $mappingNodeKey ??= $this->mappingNodeRepository->create($dataType, $portalNodeKey);
            $mappingNode = $this->mappingNodeRepository->read($mappingNodeKey);

            $mapping = (new MappingStruct($portalNodeKey, $mappingNode))->setExternalId($primaryKey);

            return new MappedDatasetEntityStruct($mapping, $entity);
        }, iterable_to_array($trackedEntities));

        return new MappedDatasetEntityCollection($mappedDatasetEntities);
    }

    private function reflectTrackedEntities(
        MappedDatasetEntityCollection $trackedEntities,
        PortalNodeKeyInterface $portalNodeKey
    ): void {
        /** @var MappedDatasetEntityStruct $trackedEntity */
        foreach ($trackedEntities as $trackedEntity) {
            $sourceMapping = $trackedEntity->getMapping();
            $targetMapping = $this->mappingService->reflect($sourceMapping, $portalNodeKey);

            $reflectionMapping = (new ReflectionMapping())
                ->setPortalNodeKey($sourceMapping->getPortalNodeKey())
                ->setMappingNodeKey($sourceMapping->getMappingNodeKey())
                ->setDatasetEntityClassName($sourceMapping->getDatasetEntityClassName())
                ->setExternalId($sourceMapping->getExternalId())
            ;

            $trackedEntity->getDatasetEntity()->attach($reflectionMapping);
            $trackedEntity->getDatasetEntity()->setPrimaryKey($targetMapping->getExternalId());
        }
    }
}
