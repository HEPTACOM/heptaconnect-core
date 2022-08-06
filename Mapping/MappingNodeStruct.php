<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

final class MappingNodeStruct implements MappingNodeStructInterface
{
    private MappingNodeKeyInterface $id;

    private EntityType $entityType;

    public function __construct(MappingNodeKeyInterface $id, EntityType $entityType)
    {
        $this->id = $id;
        $this->entityType = $entityType;
    }

    public function getKey(): MappingNodeKeyInterface
    {
        return $this->id;
    }

    public function setId(MappingNodeKeyInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEntityType(): EntityType
    {
        return $this->entityType;
    }

    public function setEntityType(EntityType $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }
}
