<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Portal\Base\Contract\StorageMappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;

class MappingNodeStruct implements MappingNodeStructInterface
{
    private StorageMappingNodeKeyInterface $id;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
     */
    private string $datasetEntityClassName;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     */
    public function __construct(StorageMappingNodeKeyInterface $id, string $datasetEntityClassName)
    {
        $this->id = $id;
        $this->datasetEntityClassName = $datasetEntityClassName;
    }

    public function getKey(): StorageMappingNodeKeyInterface
    {
        return $this->id;
    }

    public function setId(StorageMappingNodeKeyInterface $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
     */
    public function getDatasetEntityClassName(): string
    {
        return $this->datasetEntityClassName;
    }

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     */
    public function setDatasetEntityClassName(string $datasetEntityClassName): self
    {
        $this->datasetEntityClassName = $datasetEntityClassName;

        return $this;
    }
}
