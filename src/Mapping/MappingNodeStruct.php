<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

class MappingNodeStruct
{
    private string $id;

    private string $datasetEntityClassName;

    public function __construct(string $id, string $datasetEntityClassName)
    {
        $this->id = $id;
        $this->datasetEntityClassName = $datasetEntityClassName;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDatasetEntityClassName(): string
    {
        return $this->datasetEntityClassName;
    }

    public function setDatasetEntityClassName(string $datasetEntityClassName): self
    {
        $this->datasetEntityClassName = $datasetEntityClassName;

        return $this;
    }
}
