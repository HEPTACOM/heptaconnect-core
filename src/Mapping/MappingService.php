<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingExceptionRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;

class MappingService implements MappingServiceInterface
{
    private MappingRepositoryContract $mappingRepository;

    private MappingExceptionRepositoryContract $mappingExceptionRepository;

    public function __construct(
        MappingRepositoryContract $mappingRepository,
        MappingExceptionRepositoryContract $mappingExceptionRepository
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->mappingExceptionRepository = $mappingExceptionRepository;
    }

    public function addException(MappingInterface $mapping, \Throwable $exception): void
    {
        $mappingKeys = $this->mappingRepository->listByNodes(
            $mapping->getMappingNodeKey(),
            $mapping->getPortalNodeKey()
        );
        $mappingKey = null;

        foreach ($mappingKeys as $mappingKey) {
            $mappingKey = $this->mappingRepository->updateExternalId($mappingKey, $mapping->getExternalId());
            break;
        }

        if (!$mappingKey instanceof MappingKeyInterface) {
            $mappingKey = $this->mappingRepository->create(
                $mapping->getPortalNodeKey(),
                $mapping->getMappingNodeKey(),
                $mapping->getExternalId()
            );
        }

        $this->mappingExceptionRepository->create($mappingKey, $exception);
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
