<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use DeepCopy\DeepCopy;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Support\ReflectionMapping;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityTrackerContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector;
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

    private EntityReflector $entityReflector;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        RouteRepositoryContract $routeRepository,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository,
        DatasetEntityTrackerContract $datasetEntityTracker,
        EntityMapperContract $entityMapper,
        EntityReflector $entityReflector
    ) {
        $this->deepCopy = new DeepCopy();
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->routeRepository = $routeRepository;
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->datasetEntityTracker = $datasetEntityTracker;
        $this->datasetEntityTracker->deny(ReflectionMapping::class);
        $this->entityMapper = $entityMapper;
        $this->entityReflector = $entityReflector;
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

        $trackedEntities = $this->entityMapper->mapEntities($message->getTrackedEntities(), $portalNodeKey);
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
                fn (DatasetEntityInterface $entity) => !$entity instanceof ReflectionMapping
            );

            /** @var MappedDatasetEntityStruct $trackedEntity */
            foreach ($trackedEntities as $trackedEntity) {
                $trackedEntity->getDatasetEntity()->unattach(ReflectionMapping::class);
            }

            $typedMappedDatasetEntityCollection->push([
                new MappedDatasetEntityStruct($targetMapping, $datasetEntity),
            ]);
        }

        $this->receiveService->receive(
            $typedMappedDatasetEntityCollection,
            function (PortalNodeKeyInterface $targetPortalNodeKey) use ($receivedEntityData) {
                $originalReflectionMappingsByType = [];

                foreach ($receivedEntityData as $receivedEntities) {
                    foreach ($receivedEntities as $receivedEntity) {
                        if (!$receivedEntity instanceof DatasetEntityInterface
                            || $receivedEntity->getPrimaryKey() === null) {
                            continue;
                        }

                        $original = $receivedEntity->getAttachment(ReflectionMapping::class);

                        if (!$original instanceof ReflectionMapping || $original->getExternalId() === null) {
                            continue;
                        }

                        $originalReflectionMappingsByType[\get_class($receivedEntity)][$receivedEntity->getPrimaryKey()] = $original;
                    }
                }

                foreach ($originalReflectionMappingsByType as $datasetEntityType => $originalReflectionMappings) {
                    $externalIds = \array_keys($originalReflectionMappings);
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
