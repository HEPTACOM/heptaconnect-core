<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\EmittedEntitiesToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;

final class ReceiveJobDispatchingEmitter extends AbstractBufferedResultProcessingEmitter
{
    private EmittedEntitiesToJobsConverterInterface $emissionResultToJobConverter;

    private JobDispatcherContract $jobDispatcher;

    public function __construct(
        EntityType $entityType,
        EmittedEntitiesToJobsConverterInterface $emissionResultToJobConverter,
        JobDispatcherContract $jobDispatcher,
        int $batchSize
    ) {
        parent::__construct($entityType, $batchSize);

        $this->emissionResultToJobConverter = $emissionResultToJobConverter;
        $this->jobDispatcher = $jobDispatcher;
    }

    protected function processBuffer(TypedDatasetEntityCollection $buffer, EmitContextInterface $context): void
    {
        $jobs = $this->emissionResultToJobConverter->convert($context->getPortalNodeKey(), $buffer);
        $this->jobDispatcher->dispatch($jobs);
    }
}
