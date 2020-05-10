<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Component\Messenger\SourcePortalNodeStamp;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class Publisher implements PublisherInterface
{
    private StorageInterface $storage;

    private MessageBusInterface $messageBus;

    public function __construct(StorageInterface $storage, MessageBusInterface $messageBus)
    {
        $this->storage = $storage;
        $this->messageBus = $messageBus;
    }

    public function publish(string $datasetEntityClassName, string $portalNodeId, string $externalId): MappingInterface
    {
        [$mappingNode] = $this->storage->createMappingNodes([$datasetEntityClassName]);
        $mapping = (new MappingStruct($portalNodeId, $mappingNode))->setExternalId($externalId);
        $this->storage->createMappings(new MappingCollection($mapping));

        $envelope = new Envelope($mapping, [new SourcePortalNodeStamp($mapping->getPortalNodeId())]);
        $this->messageBus->dispatch($envelope);

        return $mapping;
    }
}
