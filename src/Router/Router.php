<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use DeepCopy\DeepCopy;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntity;
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

        foreach ($routeIds as $routeId) {
            $route = $this->routeRepository->read($routeId);
            $targetMapping = $this->mappingService->reflect($mapping, $route->getTargetKey());
            $entityClassName = $targetMapping->getDatasetEntityClassName();

            $typedMappedDatasetEntityCollections[$entityClassName] ??= new TypedMappedDatasetEntityCollection($entityClassName);

            $this->reflectTrackedEntities($trackedEntities, $route->getTargetKey());
            $datasetEntity = $this->deepCopy->copy($mappedDatasetEntityStruct->getDatasetEntity());

            $typedMappedDatasetEntityCollections[$entityClassName]->push([
                new MappedDatasetEntityStruct($targetMapping, $datasetEntity),
            ]);
        }

        foreach ($typedMappedDatasetEntityCollections as $typedMappedDatasetEntityCollection) {
            $this->receiveService->receive($typedMappedDatasetEntityCollection);
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
            $trackedEntity->getDatasetEntity()->setPrimaryKey(
                $this->mappingService->reflect($trackedEntity->getMapping(), $portalNodeKey)->getExternalId()
            );
        }
    }
}
