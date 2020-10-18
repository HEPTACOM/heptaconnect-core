<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\BatchPublishMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Publisher implements PublisherInterface
{
    private MessageBusInterface $messageBus;

    private MappingServiceInterface $mappingService;

    public function __construct(
        MessageBusInterface $messageBus,
        MappingServiceInterface $mappingService
    ) {
        $this->messageBus = $messageBus;
        $this->mappingService = $mappingService;
    }

    public function publish(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeId,
        string $externalId
    ): MappingInterface {
        $mapping = $this->mappingService->get($datasetEntityClassName, $portalNodeId, $externalId);

        $this->messageBus->dispatch(new PublishMessage($mapping));

        return $mapping;
    }

    public function publishBatch(MappingCollection $mappings): void
    {
        $this->messageBus->dispatch(new BatchPublishMessage($mappings));
    }
}
