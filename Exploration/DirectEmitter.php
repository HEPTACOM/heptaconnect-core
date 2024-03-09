<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

final class DirectEmitter extends EmitterContract
{
    private DatasetEntityCollection $entities;

    public function __construct(
        private EntityType $supports
    ) {
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

    public function getEntities(): DatasetEntityCollection
    {
        return $this->entities;
    }

    protected function supports(): string
    {
        return (string) $this->supports;
    }

    protected function batch(iterable $externalIds, EmitContextInterface $context): iterable
    {
        $externalIds = \iterable_to_array($externalIds);
        $type = $this->supports();

        return $this->entities->filter(
            static fn (DatasetEntityContract $entity): bool => \is_string($entity->getPrimaryKey())
                && \in_array($entity->getPrimaryKey(), $externalIds, true)
                && \is_a($entity, $type)
        );
    }
}
