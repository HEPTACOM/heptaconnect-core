<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class MappingService implements MappingServiceInterface
{
    private StorageInterface $storage;

    private MappingRepositoryContract $mappingRepository;

    public function __construct(StorageInterface $storage, MappingRepositoryContract $mappingRepository)
    {
        $this->storage = $storage;
        $this->mappingRepository = $mappingRepository;
    }

    public function addException(MappingInterface $mapping, \Throwable $exception): void
    {
        $this->storage->addMappingException($mapping, $exception);
    }

    public function save(MappingInterface $mapping): void
    {
        $mappingKeys = $this->mappingRepository->listByNodes(
            $mapping->getMappingNodeKey(),
            $mapping->getPortalNodeKey()
        );

        foreach ($mappingKeys as $mappingKey) {
            $this->mappingRepository->updateExternalId($mappingKey, $mapping->getExternalId());
            return;
        }

        $this->mappingRepository->create(
            $mapping->getPortalNodeKey(),
            $mapping->getMappingNodeKey(),
            $mapping->getExternalId()
        );
    }

    public function reflect(MappingInterface $mapping, PortalNodeKeyInterface $portalNodeKey): MappingInterface
    {
        $this->createIfNeeded($mapping);
        $mappingKeys = $this->mappingRepository->listByNodes($mapping->getMappingNodeKey(), $portalNodeKey);

        foreach ($mappingKeys as $mappingKey) {
            return $this->mappingRepository->read($mappingKey);
        }

        $mappingNode = new MappingNodeStruct($mapping->getMappingNodeKey(), $mapping->getDatasetEntityClassName());

        return new MappingStruct($portalNodeKey, $mappingNode);
    }

    private function createIfNeeded(MappingInterface $mapping): void
    {
        $mappingKeys = $this->mappingRepository->listByNodes(
            $mapping->getMappingNodeKey(),
            $mapping->getPortalNodeKey()
        );

        foreach ($mappingKeys as $_) {
            return;
        }

        $this->mappingRepository->create(
            $mapping->getPortalNodeKey(),
            $mapping->getMappingNodeKey(),
            $mapping->getExternalId()
        );
    }
}
