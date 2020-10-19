<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Support;

use Heptacom\HeptaConnect\Dataset\Base\DatasetEntity;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ReflectionMapping extends DatasetEntity implements MappingInterface
{
    protected ?string $externalId = null;

    protected ?PortalNodeKeyInterface $portalNodeKey = null;

    protected ?MappingNodeKeyInterface $mappingNodeKey = null;

    protected ?string $datasetEntityClassName = null;


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
        return $this->portalNodeKey;
    }

    public function setPortalNodeKey(PortalNodeKeyInterface $portalNodeKey): self
    {
        $this->portalNodeKey = $portalNodeKey;

        return $this;
    }

    public function getMappingNodeKey(): MappingNodeKeyInterface
    {
        return $this->mappingNodeKey;
    }

    public function setMappingNodeKey(MappingNodeKeyInterface $mappingNodeKey): self
    {
        $this->mappingNodeKey = $mappingNodeKey;

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
