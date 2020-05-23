<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StorageMappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StoragePortalNodeKeyInterface;

class MappingStruct implements MappingInterface
{
    private StoragePortalNodeKeyInterface $portalNodeId;

    private StorageMappingNodeKeyInterface $mappingNodeKey;

    public function __construct(
        StoragePortalNodeKeyInterface $portalNodeId,
        StorageMappingNodeKeyInterface $mappingNodeKey
    ) {
        $this->portalNodeId = $portalNodeId;
        $this->mappingNodeKey = $mappingNodeKey;
    }

    public function getExternalId(): string
    {
        return __METHOD__;
    }

    public function setExternalId(string $externalId): MappingInterface
    {
        return $this;
    }

    public function getMappingNodeKey(): StorageMappingNodeKeyInterface
    {
        return $this->mappingNodeKey;
    }

    public function getPortalNodeKey(): StoragePortalNodeKeyInterface
    {
        return $this->portalNodeId;
    }

    public function getDatasetEntityClassName(): string
    {
        return FooBarEntity::class;
    }
}
