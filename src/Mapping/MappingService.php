<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingExceptionRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

class MappingService implements MappingServiceInterface
{
    private MappingRepositoryContract $mappingRepository;

    private MappingExceptionRepositoryContract $mappingExceptionRepository;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        MappingRepositoryContract $mappingRepository,
        MappingExceptionRepositoryContract $mappingExceptionRepository,
        MappingNodeRepositoryContract $mappingNodeRepository,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->mappingExceptionRepository = $mappingExceptionRepository;
        $this->mappingNodeRepository = $mappingNodeRepository;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function addException(MappingInterface $mapping, \Throwable $exception): void
    {
        $mappingKeys = $this->mappingRepository->listByNodes(
            $mapping->getMappingNodeKey(),
            $mapping->getPortalNodeKey()
        );
        $mappingKey = null;

        foreach ($mappingKeys as $mappingKey) {
            $this->mappingRepository->updateExternalId($mappingKey, $mapping->getExternalId());
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

    public function merge(MappingNodeKeyInterface $mergeFrom, MappingNodeKeyInterface $mergeInto): void
    {
        try {
            $nodeFrom = $this->mappingNodeRepository->read($mergeFrom);
            $nodeInto = $this->mappingNodeRepository->read($mergeInto);
            if ($nodeFrom->getDatasetEntityClassName() !== $nodeInto->getDatasetEntityClassName()) {
                throw new MappingNodeAreUnmergableException($mergeFrom, $mergeInto);
            }

            $fromPortalExistences = [];

            foreach ($this->mappingRepository->listByMappingNode($mergeInto) as $mappingKey) {
                $mapping = $this->mappingRepository->read($mappingKey);
                $portalNode = $this->storageKeyGenerator->serialize($mapping->getPortalNodeKey());

                $fromPortalExistences[$portalNode] = $mapping->getExternalId();
            }

            /** @var MappingInterface[] $mappingsToCreate */
            $mappingsToCreate = [];
            /** @var MappingKeyInterface[] $mappingsToDelete */
            $mappingsToDelete = [];

            foreach ($this->mappingRepository->listByMappingNode($mergeFrom) as $mappingKey) {
                $mapping = $this->mappingRepository->read($mappingKey);
                $portalNode = $this->storageKeyGenerator->serialize($mapping->getPortalNodeKey());

                if (\array_key_exists($portalNode, $fromPortalExistences)) {
                    if ($fromPortalExistences[$portalNode] !== $mapping->getExternalId()) {
                        throw new MappingNodeAreUnmergableException($mergeFrom, $mergeInto);
                    }

                } else {
                    $mappingsToCreate[] = $mapping;
                }

                $mappingsToDelete[] = $mappingKey;
            }

            \array_walk($mappingsToDelete, [$this->mappingRepository, 'delete']);

            foreach ($mappingsToCreate as $mapping) {
                $this->mappingRepository->create($mapping->getPortalNodeKey(), $mergeInto, $mapping->getExternalId());
            }

            $this->mappingNodeRepository->delete($mergeFrom);
        } catch (NotFoundException $e) {
            throw new MappingNodeAreUnmergableException($mergeFrom, $mergeInto, $e);
        } catch (UnsupportedStorageKeyException $e) {
            throw new MappingNodeAreUnmergableException($mergeFrom, $mergeInto, $e);
        }
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
