<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeNotCreatedException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class Publisher implements PublisherInterface
{
    private StorageInterface $storage;

    private MessageBusInterface $messageBus;

    private MappingRepositoryContract $mappingRepository;

    public function __construct(
        StorageInterface $storage,
        MessageBusInterface $messageBus,
        MappingRepositoryContract $mappingRepository
    ) {
        $this->storage = $storage;
        $this->messageBus = $messageBus;
        $this->mappingRepository = $mappingRepository;
    }

    public function publish(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeId,
        string $externalId
    ): MappingInterface {
        $mappingNode = $this->storage->getMappingNode($datasetEntityClassName, $portalNodeId, $externalId);
        $mappingExists = $mappingNode instanceof MappingNodeStructInterface;

        if (!$mappingNode instanceof MappingNodeStructInterface) {
            $mappingNode = $this->storage->createMappingNodes([$datasetEntityClassName], $portalNodeId)->first();
        }

        if (!$mappingNode instanceof MappingNodeStructInterface) {
            throw new MappingNodeNotCreatedException();
        }

        $mapping = (new MappingStruct($portalNodeId, $mappingNode))->setExternalId($externalId);

        if (!$mappingExists) {
            $this->mappingRepository->create(
                $mapping->getPortalNodeKey(),
                $mapping->getMappingNodeKey(),
                $mapping->getExternalId()
            );
        }

        $this->messageBus->dispatch(new PublishMessage($mapping));

        return $mapping;
    }
}
