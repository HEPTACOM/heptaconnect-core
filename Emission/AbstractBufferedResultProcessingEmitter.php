<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

/**
 * Base class for emitters that capture results from a stack and process its result in batch size amount of items.
 */
abstract class AbstractBufferedResultProcessingEmitter extends EmitterContract
{
    private TypedDatasetEntityCollection $buffer;

    public function __construct(private EntityType $entityType, private int $batchSize)
    {
        $this->buffer = $this->createBuffer();
    }

    public function emit(iterable $externalIds, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        try {
            yield from $this->emitNext($stack, $externalIds, $context);
        } finally {
            $this->dispatchBuffer($context);
        }
    }

    protected function supports(): string
    {
        return (string) $this->entityType;
    }

    protected function extend(DatasetEntityContract $entity, EmitContextInterface $context): DatasetEntityContract
    {
        $this->pushBuffer($entity, $this->buffer, $context);

        if ($this->buffer->count() >= $this->batchSize) {
            $this->dispatchBuffer($context);
        }

        return $entity;
    }

    /**
     * Processes a batch of the buffer. The buffer is not more than batchSize items, but can be less.
     */
    abstract protected function processBuffer(
        TypedDatasetEntityCollection $buffer,
        EmitContextInterface $context
    ): void;

    /**
     * Any data that is returned by the stack is running through this. Here can be filtered what will be buffered.
     */
    protected function pushBuffer(
        DatasetEntityContract $value,
        TypedDatasetEntityCollection $buffer,
        EmitContextInterface $context
    ): void {
        $buffer->push([$value]);
    }

    private function dispatchBuffer(EmitContextInterface $context): void
    {
        foreach ($this->buffer->chunk(\max(1, $this->batchSize)) as $slice) {
            $this->processBuffer($slice, $context);
        }

        $this->buffer->clear();
    }

    private function createBuffer(): TypedDatasetEntityCollection
    {
        return new TypedDatasetEntityCollection($this->getSupportedEntityType());
    }
}
