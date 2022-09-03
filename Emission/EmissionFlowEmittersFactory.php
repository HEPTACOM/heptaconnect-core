<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\EmittedEntitiesToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class EmissionFlowEmittersFactory implements EmissionFlowEmittersFactoryInterface
{
    private EmittedEntitiesToJobsConverterInterface $emittedEntitiesToJobsConverter;

    private JobDispatcherContract $jobDispatcher;

    private int $jobBatchSize;

    public function __construct(
        EmittedEntitiesToJobsConverterInterface $emittedEntitiesToJobsConverter,
        JobDispatcherContract $jobDispatcher,
        int $jobBatchSize
    ) {
        $this->emittedEntitiesToJobsConverter = $emittedEntitiesToJobsConverter;
        $this->jobDispatcher = $jobDispatcher;
        $this->jobBatchSize = $jobBatchSize;
    }

    public function createEmitters(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): EmitterCollection
    {
        return new EmitterCollection([
            new ReceiveJobDispatchingEmitter(
                $entityType,
                $this->emittedEntitiesToJobsConverter,
                $this->jobDispatcher,
                $this->jobBatchSize
            ),
        ]);
    }
}
