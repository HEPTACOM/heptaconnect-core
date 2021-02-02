<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\ForeignKeyAwareInterface;

class PrimaryKeyChangesAttachable implements AttachableInterface, ForeignKeyAwareInterface
{
    /**
     * @psalm-var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $datasetEntityClassName
     */
    private string $datasetEntityClassName;

    private array $foreignKeys = [];

    private ?string $foreignKey = null;

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $datasetEntityClassName
     */
    public function __construct(string $datasetEntityClassName)
    {
        $this->datasetEntityClassName = $datasetEntityClassName;
    }

    public function getForeignDatasetEntityClassName(): string
    {
        return $this->datasetEntityClassName;
    }

    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    public function getFirstForeignKey(): ?string
    {
        return \reset($this->foreignKeys) ?: null;
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
