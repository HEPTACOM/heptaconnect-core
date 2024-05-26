<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

final class MappingStruct implements MappingInterface
{
    private ?string $externalId = null;

    public function __construct(
        private readonly PortalNodeKeyInterface $portalNodeId,
        private readonly MappingNodeStructInterface $mappingNodeStruct
    ) {
    }

    #[\Override]
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    #[\Override]
    public function setExternalId(?string $externalId): MappingInterface
    {
        $this->externalId = $externalId;

        return $this;
    }

    #[\Override]
    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeId;
    }

    #[\Override]
    public function getMappingNodeKey(): MappingNodeKeyInterface
    {
        return $this->mappingNodeStruct->getKey();
    }

    #[\Override]
    public function getEntityType(): EntityType
    {
        return $this->mappingNodeStruct->getEntityType();
    }
}
