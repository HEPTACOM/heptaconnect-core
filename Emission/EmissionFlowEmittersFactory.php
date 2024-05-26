<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\EmittedEntitiesToJobsConverterInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final readonly class EmissionFlowEmittersFactory implements EmissionFlowEmittersFactoryInterface
{
    public function __construct(
        private EmittedEntitiesToJobsConverterInterface $emittedEntityToJobsConverter,
        private JobDispatcherContract $jobDispatcher,
        private int $jobBatchSize
    ) {
    }

    #[\Override]
    public function createEmitters(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): EmitterCollection
    {
        return new EmitterCollection([
            new ReceiveJobDispatchingEmitter(
                $entityType,
                $this->emittedEntityToJobsConverter,
                $this->jobDispatcher,
                $this->jobBatchSize
            ),
        ]);
    }
}
