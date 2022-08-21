<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\EmittedEntitiesToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class ReceiveJobDispatchingEmitter extends EmitterContract
{
    private TypedDatasetEntityCollection $buffer;

    private EntityType $entityType;

    private EmittedEntitiesToJobsConverterInterface $emissionResultToJobConverter;

    private JobDispatcherContract $jobDispatcher;

    private int $batchSize;

    public function __construct(
        EntityType $entityType,
        EmittedEntitiesToJobsConverterInterface $emissionResultToJobConverter,
        JobDispatcherContract $jobDispatcher,
        int $batchSize
    ) {
        $this->buffer = new TypedDatasetEntityCollection($entityType);
        $this->entityType = $entityType;
        $this->emissionResultToJobConverter = $emissionResultToJobConverter;
        $this->jobDispatcher = $jobDispatcher;
        $this->batchSize = $batchSize;
    }

    public function emit(iterable $externalIds, EmitContextInterface $context, EmitterStackInterface $stack): iterable
    {
        $this->buffer = new TypedDatasetEntityCollection($this->buffer->getEntityType());

        try {
            foreach ($this->emitNext($stack, $externalIds, $context) as $key => $value) {
                yield $key => $value;
            }
        } finally {
            while ($this->buffer->count() > 0) {
                $this->dispatchBuffer($context->getPortalNodeKey());
            }
        }
    }

    protected function extend(DatasetEntityContract $entity, EmitContextInterface $context): DatasetEntityContract
    {
        $this->buffer->push([$entity]);

        if ($this->buffer->count() >= $this->batchSize) {
            $this->dispatchBuffer($context->getPortalNodeKey());
        }

        return $entity;
    }

    protected function supports(): string
    {
        return (string) $this->entityType;
    }

    private function dispatchBuffer(PortalNodeKeyInterface $portalNodeKey): void
    {
        $batchSize = $this->batchSize;
        $buffer = $this->buffer;
        $entities = [];

        for ($step = 0; $step < $batchSize && $buffer->count() > 0; ++$step) {
            /** @var DatasetEntityContract $item */
            $item = $buffer->shift();
            $entities[] = $item;
        }

        $jobs = $this->emissionResultToJobConverter->convert($portalNodeKey, new DatasetEntityCollection($entities));
        $this->jobDispatcher->dispatch($jobs);
    }
}
