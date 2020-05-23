<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StorageMappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StoragePortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

class MappingStruct implements MappingInterface
{
    private ?string $externalId = null;

    private StoragePortalNodeKeyInterface $portalNodeId;

    private MappingNodeStructInterface $mappingNodeStruct;

    public function __construct(
        StoragePortalNodeKeyInterface $portalNodeId,
        MappingNodeStructInterface $mappingNodeStruct
    ) {
        $this->portalNodeId = $portalNodeId;
        $this->mappingNodeStruct = $mappingNodeStruct;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): MappingInterface
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getPortalNodeKey(): StoragePortalNodeKeyInterface
    {
        return $this->portalNodeId;
    }

    public function getMappingNodeKey(): StorageMappingNodeKeyInterface
    {
        return $this->mappingNodeStruct->getKey();
    }

    public function getDatasetEntityClassName(): string
    {
        return $this->mappingNodeStruct->getDatasetEntityClassName();
    }
}
