<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\TypedMappingCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class Router implements RouterInterface, MessageSubscriberInterface
{
    private EmitServiceInterface $emitService;

    private ReceiveServiceInterface $receiveService;

    private PortalNodeRegistryInterface $portalNodeRegistry;

    private StorageInterface $storage;

    private MappingServiceInterface $mappingService;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        PortalNodeRegistryInterface $portalNodeRegistry,
        StorageInterface $storage,
        MappingServiceInterface $mappingService
    ) {
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->portalNodeRegistry = $portalNodeRegistry;
        $this->storage = $storage;
        $this->mappingService = $mappingService;
    }

    public static function getHandledMessages(): iterable
    {
        yield PublishMessage::class => ['method' => 'handlePublishMessage'];
        yield EmitMessage::class => ['method' => 'handleEmitMessage'];
    }

    public function handlePublishMessage(PublishMessage $message): void
    {
        $mapping = $message->getMapping();

        $this->emitService->emit(new TypedMappingCollection($mapping->getDatasetEntityClassName(), [$mapping]));
    }

    public function handleEmitMessage(EmitMessage $message): void
    {
        $mappedDatasetEntityStruct = $message->getMappedDatasetEntityStruct();
        $mapping = $mappedDatasetEntityStruct->getMapping();

        $targetPortalNodeIds = $this->storage->getRouteTargets(
            $mapping->getPortalNodeKey(),
            $mapping->getDatasetEntityClassName()
        );

        $typedMappedDatasetEntityCollections = [];

        foreach ($targetPortalNodeIds as $targetPortalNodeId) {
            $targetMapping = $this->mappingService->reflect($mapping, $targetPortalNodeId);
            $entityClassName = $targetMapping->getDatasetEntityClassName();

            $typedMappedDatasetEntityCollections[$entityClassName] ??= new TypedMappedDatasetEntityCollection($entityClassName);
            $typedMappedDatasetEntityCollections[$entityClassName]->push([
                new MappedDatasetEntityStruct($targetMapping, $mappedDatasetEntityStruct->getDatasetEntity()),
            ]);
        }

        foreach ($typedMappedDatasetEntityCollections as $typedMappedDatasetEntityCollection) {
            $this->receiveService->receive($typedMappedDatasetEntityCollection);
        }
    }
}
