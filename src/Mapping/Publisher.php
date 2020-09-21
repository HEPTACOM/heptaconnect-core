<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Component\Messenger\Message\PublishMessage;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeNotCreatedException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Symfony\Component\Messenger\MessageBusInterface;

class Publisher implements PublisherInterface
{
    private MessageBusInterface $messageBus;

    private MappingRepositoryContract $mappingRepository;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        MappingRepositoryContract $mappingRepository,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->messageBus = $messageBus;
        $this->mappingRepository = $mappingRepository;
        $this->mappingNodeRepository = $mappingNodeRepository;
    }

    public function publish(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeId,
        string $externalId
    ): MappingInterface {
        $mappingNodeId = $this->getMappingNodeId($datasetEntityClassName, $portalNodeId, $externalId);
        $mappingExists = $mappingNodeId instanceof MappingNodeKeyInterface;

        if (!$mappingExists) {
            $mappingNodeId = $this->mappingNodeRepository->create($datasetEntityClassName, $portalNodeId);
        }

        if (!$mappingNodeId instanceof MappingNodeKeyInterface) {
            throw new MappingNodeNotCreatedException();
        }

        $mapping = (new MappingStruct($portalNodeId, $this->mappingNodeRepository->read($mappingNodeId)))
            ->setExternalId($externalId);

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

    private function getMappingNodeId(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): ?MappingNodeKeyInterface {
        $ids = $this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalId(
            $datasetEntityClassName,
            $portalNodeKey,
            $externalId
        );

        foreach ($ids as $id) {
            return $id;
        }

        return null;
    }
}
