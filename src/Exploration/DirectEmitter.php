<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;

class DirectEmitter extends EmitterContract
{
    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $supports;

    private MappedDatasetEntityCollection $mappedEntities;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $supports
     */
    public function __construct(string $supports)
    {
        $this->supports = $supports;
        $this->mappedEntities = new MappedDatasetEntityCollection();
    }

    public function supports(): string
    {
        return $this->supports;
    }

    public function getMappedEntities(): MappedDatasetEntityCollection
    {
        return $this->mappedEntities;
    }

    protected function run(MappingInterface $mapping, EmitContextInterface $context): ?DatasetEntityContract
    {
        /** @var MappedDatasetEntityStruct $match */
        foreach ($this->mappedEntities->filter($this->matchesMapping($mapping)) as $match) {
            return $match->getDatasetEntity();
        }

        return null;
    }

    private function matchesMapping(MappingInterface $mapping): callable
    {
        return static fn (MappedDatasetEntityStruct $m): bool =>
            $mapping->getDatasetEntityClassName() === $m->getMapping()->getDatasetEntityClassName() &&
            $mapping->getPortalNodeKey()->equals($m->getMapping()->getPortalNodeKey()) &&
            $mapping->getExternalId() === $m->getMapping()->getExternalId();
    }
}
