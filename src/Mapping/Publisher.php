<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class Publisher implements PublisherInterface
{
    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function publish(string $datasetEntityClassName, string $portalNodeId, string $externalId): MappingInterface
    {
        [$mappingNode] = $this->storage->createMappingNodes([$datasetEntityClassName]);
        $mapping = (new MappingStruct($portalNodeId, $mappingNode))->setExternalId($externalId);
        $this->storage->createMappings(new MappingCollection($mapping));

        return $mapping;
    }
}
