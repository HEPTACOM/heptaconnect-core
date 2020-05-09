<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;

class MappingStruct implements MappingInterface
{
    public function getExternalId(): string
    {
        return __METHOD__;
    }

    public function setExternalId(string $externalId): MappingInterface
    {
        return $this;
    }

    public function getPortalNodeId(): string
    {
        return __METHOD__;
    }

    public function getDatasetEntityClassName(): string
    {
        return FooBarEntity::class;
    }
}
