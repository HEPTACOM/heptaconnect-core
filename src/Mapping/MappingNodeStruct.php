<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

class MappingNodeStruct implements MappingNodeStructInterface
{
    private MappingNodeKeyInterface $id;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $datasetEntityClassName;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $datasetEntityClassName
     */
    public function __construct(MappingNodeKeyInterface $id, string $datasetEntityClassName)
    {
        $this->id = $id;
        $this->datasetEntityClassName = $datasetEntityClassName;
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

    /**
     * @return class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    public function getDatasetEntityClassName(): string
    {
        return $this->datasetEntityClassName;
    }

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $datasetEntityClassName
     */
    public function setDatasetEntityClassName(string $datasetEntityClassName): self
    {
        $this->datasetEntityClassName = $datasetEntityClassName;

        return $this;
    }
}
