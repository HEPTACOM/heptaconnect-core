<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;

class DirectEmitter extends EmitterContract
{
    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $supports;

    private DatasetEntityCollection $entities;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $supports
     */
    public function __construct(string $supports)
    {
        $this->supports = $supports;
        $this->entities = new DatasetEntityCollection();
    }

    public function supports(): string
    {
        return $this->supports;
    }

    public function getEntities(): DatasetEntityCollection
    {
        return $this->entities;
    }

    protected function run(string $externalId, EmitContextInterface $context): ?DatasetEntityContract
    {
        $matches = $this->entities->filter(
            fn (DatasetEntityContract $entity): bool => $entity->getPrimaryKey() === $externalId &&
                \is_a($entity, $this->supports())
        );

        /** @var DatasetEntityContract $match */
        foreach ($matches as $match) {
            return $match;
        }

        return null;
    }
}
