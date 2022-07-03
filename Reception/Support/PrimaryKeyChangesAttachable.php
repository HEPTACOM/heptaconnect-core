<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\Contract\ForeignKeyAwareInterface;
use Heptacom\HeptaConnect\Dataset\Base\Support\EntityTypeClassString;

class PrimaryKeyChangesAttachable implements AttachableInterface, ForeignKeyAwareInterface
{
    /**
     * @psalm-var class-string<DatasetEntityContract> $entityType
     */
    private string $entityType;

    private array $foreignKeys = [];

    private ?string $foreignKey = null;

    public function __construct(EntityTypeClassString $entityType)
    {
        $this->entityType = $entityType->getClassString();
    }

    public function getForeignEntityType(): EntityTypeClassString
    {
        return new EntityTypeClassString($this->entityType);
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getFirstForeignKey(): ?string
    {
        $result = \current($this->foreignKeys);

        return \is_string($result) ? $result : null;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreignKey;
    }

    public function setForeignKey(?string $foreignKey): void
    {
        if ($this->foreignKeys === [] || $this->foreignKey !== $foreignKey) {
            $this->foreignKeys[] = $foreignKey;
        }

        $this->foreignKey = $foreignKey;
    }
}
