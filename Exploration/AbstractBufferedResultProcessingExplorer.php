<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

/**
 * Base class for explorers that capture results from a stack and process its result in batch size amount of items.
 *
 * @template T
 */
abstract class AbstractBufferedResultProcessingExplorer extends ExplorerContract
{
    private EntityType $entityType;

    private int $batchSize;

    public function __construct(EntityType $entityType, int $batchSize)
    {
        $this->entityType = $entityType;
        $this->batchSize = $batchSize;
    }

    public function explore(ExploreContextInterface $context, ExplorerStackInterface $stack): iterable
    {
        $buffer = $this->createBuffer();

        try {
            foreach ($this->exploreNext($context, $stack) as $key => $value) {
                $this->pushBuffer($value, $buffer, $context);

                while ($buffer->count() >= $this->batchSize) {
                    $this->dispatchBuffer($buffer, $context);
                }

                yield $key => $value;
            }
        } finally {
            while ($buffer->count() > 0) {
                $this->dispatchBuffer($buffer, $context);
            }
        }
    }

    protected function supports(): string
    {
        return (string) $this->entityType;
    }

    /**
     * Creates an empty buffer.
     *
     * @return CollectionInterface<T>
     */
    abstract protected function createBuffer(): CollectionInterface;

    /**
     * Processes a batch of the buffer. The buffer is not more than batchSize items, but can be less.
     *
     * @param CollectionInterface<T> $buffer
     */
    abstract protected function processBuffer(CollectionInterface $buffer, ExploreContextInterface $context): void;

    /**
     * Any data that is returned by the stack is running through this. Here can be filtered what will be buffered.
     *
     * @param DatasetEntityContract|int|string $value
     * @param CollectionInterface<T>           $buffer
     */
    abstract protected function pushBuffer($value, CollectionInterface $buffer, ExploreContextInterface $context): void;

    /**
     * @param CollectionInterface<T> $buffer
     */
    private function dispatchBuffer(CollectionInterface $buffer, ExploreContextInterface $context): void
    {
        $batchSize = $this->batchSize;
        $splice = $this->createBuffer();

        for ($step = 0; $step < \max(1, $batchSize) && $buffer->count() > 0; ++$step) {
            /** @var T $item */
            $item = $buffer->shift();
            $splice->push([$item]);
        }

        $this->processBuffer($splice, $context);
    }
}
