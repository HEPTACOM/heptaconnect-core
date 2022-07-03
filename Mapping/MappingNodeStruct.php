<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Dataset\Base\Support\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

final class MappingNodeStruct implements MappingNodeStructInterface
{
    private MappingNodeKeyInterface $id;

    private EntityTypeClassString $entityType;

    public function __construct(MappingNodeKeyInterface $id, EntityTypeClassString $entityType)
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

    public function getEntityType(): EntityTypeClassString
    {
        return $this->entityType;
    }

    public function setEntityType(EntityTypeClassString $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }
}
