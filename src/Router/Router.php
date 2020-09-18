<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Router;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\Contract\RouterInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteRepositoryContract;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class Router implements RouterInterface, MessageSubscriberInterface
{
    private EmitServiceInterface $emitService;

    private ReceiveServiceInterface $receiveService;

    private RouteRepositoryContract $routeRepository;

    private MappingServiceInterface $mappingService;

    public function __construct(
        EmitServiceInterface $emitService,
        ReceiveServiceInterface $receiveService,
        RouteRepositoryContract $routeRepository,
        MappingServiceInterface $mappingService
    ) {
        $this->emitService = $emitService;
        $this->receiveService = $receiveService;
        $this->routeRepository = $routeRepository;
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
        $routeIds = $this->routeRepository->listBySourceAndEntityType(
            $mapping->getPortalNodeKey(),
            $mapping->getDatasetEntityClassName()
        );
        $typedMappedDatasetEntityCollections = [];

        foreach ($routeIds as $routeId) {
            $route = $this->routeRepository->read($routeId);
            $targetMapping = $this->mappingService->reflect($mapping, $route->getTargetKey());
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
