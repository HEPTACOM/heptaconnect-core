<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\EmissionHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;

class EmissionHandler implements EmissionHandlerInterface
{
    private EmitServiceInterface $emitService;

    public function __construct(EmitServiceInterface $emitService)
    {
        $this->emitService = $emitService;
    }

    public function triggerEmission(JobDataCollection $jobs): void
    {
        $emissions = [];

        /** @var JobData $job */
        foreach ($jobs as $job) {
            $emissions[$job->getMappingComponent()->getDatasetEntityClassName()][] = $job->getMappingComponent();
        }

        foreach ($emissions as $dataType => $emission) {
            foreach (\array_chunk($emission, 10) as $emissionChunk) {
                $this->emitService->emit(new TypedMappingComponentCollection($dataType, $emissionChunk));
            }
        }
    }
}
