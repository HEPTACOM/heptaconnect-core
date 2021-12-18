<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\EmissionHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Finish\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Start\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Start\JobStartPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;

class EmissionHandler implements EmissionHandlerInterface
{
    private EmitServiceInterface $emitService;

    private JobStartActionInterface $jobStartAction;

    private JobFinishActionInterface $jobFinishAction;

    public function __construct(
        EmitServiceInterface $emitService,
        JobStartActionInterface $jobStartAction,
        JobFinishActionInterface $jobFinishAction
    ) {
        $this->emitService = $emitService;
        $this->jobStartAction = $jobStartAction;
        $this->jobFinishAction = $jobFinishAction;
    }

    public function triggerEmission(JobDataCollection $jobs): void
    {
        $emissions = [];
        /** @var JobKeyInterface[][] $processed */
        $processed = [];

        /** @var JobData $job */
        foreach ($jobs as $job) {
            $emissions[$job->getMappingComponent()->getEntityType()][] = $job->getMappingComponent();
            $processed[$job->getMappingComponent()->getEntityType()][] = $job->getJobKey();
        }

        foreach ($emissions as $dataType => $emission) {
            $emissionChunks = \array_chunk($emission, 10);
            $processedChunks = \array_chunk($processed[$dataType], 10);

            foreach ($emissionChunks as $chunkKey => $emissionChunk) {
                $jobKeys = new JobKeyCollection($processedChunks[$chunkKey] ?? []);

                $this->jobStartAction->start(new JobStartPayload($jobKeys, new \DateTimeImmutable(), null));
                $this->emitService->emit(new TypedMappingComponentCollection($dataType, $emissionChunk));
                $this->jobFinishAction->finish(new JobFinishPayload($jobKeys, new \DateTimeImmutable(), null));
            }
        }
    }
}
