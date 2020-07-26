<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class MappingService implements MappingServiceInterface
{
    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function addException(MappingInterface $mapping, \Throwable $exception): void
    {
        $this->storage->addMappingException($mapping, $exception);
    }

    public function save(MappingInterface $mapping): void
    {
        $this->storage->createMappings(new MappingCollection([$mapping]));
    }

    public function reflect(MappingInterface $mapping, PortalNodeKeyInterface $portalNodeKey): MappingInterface
    {
        if (!$this->storage->getMapping($mapping->getMappingNodeKey(), $mapping->getPortalNodeKey()) instanceof MappingInterface) {
            $this->storage->createMappings(new MappingCollection([$mapping]));
        }

        $targetMapping = $this->storage->getMapping($mapping->getMappingNodeKey(), $portalNodeKey);

        if (!$targetMapping instanceof MappingInterface) {
            $mappingNode = new MappingNodeStruct($mapping->getMappingNodeKey(), $mapping->getDatasetEntityClassName());
            $targetMapping = new MappingStruct($portalNodeKey, $mappingNode);
        }

        return $targetMapping;
    }
}
