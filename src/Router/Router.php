<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\MappingNodeStruct;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class Router implements RouterInterface, MessageSubscriberInterface
{
    private EmitServiceInterface $emitService;

    private ReceiveServiceInterface $receiveService;

    private RouteRepositoryContract $routeRepository;

    private MappingServiceInterface $mappingService;

    private EntityMapperContract $entityMapper;

    private EntityReflectorContract $entityReflector;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private DeepObjectIteratorContract $objectIterator;

    private MessageBusInterface $messageBus;

    private LockFactory $lockFactory;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        RouteRepositoryContract $routeRepository,
        MappingServiceInterface $mappingService,
        EntityMapperContract $entityMapper,
        EntityReflectorContract $entityReflector,
        StorageKeyGeneratorContract $storageKeyGenerator,
        DeepObjectIteratorContract $deepObjectIterator,
        MessageBusInterface $messageBus,
        LockFactory $lockFactory
    ) {
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->routeRepository = $routeRepository;
        $this->mappingService = $mappingService;
        $this->entityMapper = $entityMapper;
        $this->entityReflector = $entityReflector;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->objectIterator = $deepObjectIterator;
        $this->messageBus = $messageBus;
        $this->lockFactory = $lockFactory;
    }

    public static function getHandledMessages(): iterable
    {
        yield PublishMessage::class => ['method' => 'handlePublishMessage'];
        yield BatchPublishMessage::class => ['method' => 'handleBatchPublishMessage'];
        yield EmitMessage::class => ['method' => 'handleEmitMessage'];
    }

    public function handlePublishMessage(PublishMessage $message): void
    {
        $mapping = $this->mappingService->get(
            $message->getDatasetEntityClassName(),
            $message->getPortalNodeKey(),
            $message->getExternalId()
        );

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
            throw new \Exception(\sprintf(\implode(\PHP_EOL, ['Message is not routed. Add a route and re-explore this entity.', 'source portal: %s', 'data type: %s', 'external id: %s']), $portalNodeId, $mapping->getDatasetEntityClassName(), $mapping->getExternalId()));
        }

        // FIXME: This is slow
        $trackedEntities = new TrackedEntityCollection($this->objectIterator->iterate($mappedDatasetEntityStruct->getDatasetEntity()));
        $mappingsToEnsure = new MappingComponentCollection();

        /** @var DatasetEntityContract $trackedEntity */
        foreach ($trackedEntities->getIterator() as $trackedEntity) {
            if (!$trackedEntity instanceof DatasetEntityContract || $trackedEntity->getPrimaryKey() === null) {
                continue;
            }

            $mappingsToEnsure->push([new MappingComponentStruct(
                $portalNodeKey,
                \get_class($trackedEntity),
                $trackedEntity->getPrimaryKey()
            )]);
        }

        $this->mappingService->ensurePersistence($mappingsToEnsure);

        // TODO: improve performance
        $trackedEntities = $this->entityMapper->mapEntities($trackedEntities, $portalNodeKey);
        $typedMappedDatasetEntityCollection = new TypedMappedDatasetEntityCollection($mapping->getDatasetEntityClassName());

        foreach ($routeIds as $routeId) {
            $route = $this->routeRepository->read($routeId);

            $lock = $this->lockFactory->createLock('ca9137ba5ec646078043b96030a00e70_' . \join('_', [
                $this->storageKeyGenerator->serialize($route->getSourceKey()),
                $this->storageKeyGenerator->serialize($route->getTargetKey()),
                $this->storageKeyGenerator->serialize($mapping->getMappingNodeKey()),
            ]));

            if (!$lock->acquire()) {
                // Re-dispatch message and delay it by 60 seconds
                $this->messageBus->dispatch(Envelope::wrap($message)->with(new DelayStamp(60000)));

                continue;
            }

            try {
                // TODO: improve performance
                $this->entityReflector->reflectEntities($trackedEntities, $route->getTargetKey());

                $datasetEntity = $mappedDatasetEntityStruct->getDatasetEntity();
                $targetMapping = (new MappingStruct($route->getTargetKey(), new MappingNodeStruct(
                    $mapping->getMappingNodeKey(),
                    $mapping->getDatasetEntityClassName()
                )))->setExternalId($datasetEntity->getPrimaryKey());

                $typedMappedDatasetEntityCollection->push([
                    new MappedDatasetEntityStruct($targetMapping, $datasetEntity),
                ]);

                $this->receiveService->receive($typedMappedDatasetEntityCollection);
            } finally {
                $lock->release();
            }
        }
    }
}
