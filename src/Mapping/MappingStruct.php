<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;

class MappingStruct implements MappingInterface
{
    private ?string $externalId = null;

    private string $portalNodeId;

    private MappingNodeStruct $mappingNodeStruct;

    public function __construct(string $portalNodeId, MappingNodeStruct $mappingNodeStruct)
    {
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

    public function getPortalNodeId(): string
    {
        return $this->portalNodeId;
    }

    public function getDatasetEntityClassName(): string
    {
        return $this->mappingNodeStruct->getDatasetEntityClassName();
    }
}
