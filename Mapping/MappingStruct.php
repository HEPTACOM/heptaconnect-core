<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

final class MappingStruct implements MappingInterface
{
    private ?string $externalId = null;

    private PortalNodeKeyInterface $portalNodeId;

    private MappingNodeStructInterface $mappingNodeStruct;

    public function __construct(
        PortalNodeKeyInterface $portalNodeId,
        MappingNodeStructInterface $mappingNodeStruct
    ) {
        $this->portalNodeId = $portalNodeId;
        $this->mappingNodeStruct = $mappingNodeStruct;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): MappingInterface
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeId;
    }

    public function getMappingNodeKey(): MappingNodeKeyInterface
    {
        return $this->mappingNodeStruct->getKey();
    }

    public function getEntityType(): EntityTypeClassString
    {
        return $this->mappingNodeStruct->getEntityType();
    }
}
