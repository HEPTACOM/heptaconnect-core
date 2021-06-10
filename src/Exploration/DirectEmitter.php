<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

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

    public function emit(iterable $externalIds, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        $externalIds = \iterable_to_array($externalIds);

        if (!$context->isDirectEmission()) {
            throw new \Exception('DirectEmitter: The DirectEmitter must not be used outside of a direct emission.');
        }

        yield from $this->emitCurrent($externalIds, $context);
        yield from $this->emitNext($stack, $externalIds, $context);
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
