<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;

class MappingStruct implements MappingInterface
{
    private PortalNodeKeyInterface $portalNodeId;

    private MappingNodeKeyInterface $mappingNodeKey;

    public function __construct(
        PortalNodeKeyInterface $portalNodeId,
        MappingNodeKeyInterface $mappingNodeKey
    ) {
        $this->portalNodeId = $portalNodeId;
        $this->mappingNodeKey = $mappingNodeKey;
    }

    public function getExternalId(): string
    {
        return __METHOD__;
    }

    public function setExternalId(string $externalId): MappingInterface
    {
        return $this;
    }

    public function getMappingNodeKey(): MappingNodeKeyInterface
    {
        return $this->mappingNodeKey;
    }

    public function getPortalNodeKey(): PortalNodeKeyInterface
    {
        return $this->portalNodeId;
    }

    public function getDatasetEntityClassName(): string
    {
        return FooBarEntity::class;
    }
}
