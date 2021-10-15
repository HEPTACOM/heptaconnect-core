<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Handler;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\EmissionHandlerInterface;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;

class EmissionHandler implements EmissionHandlerInterface
{
    private EmitServiceInterface $emitService;

    private JobRepositoryContract $jobRepository;

    public function __construct(EmitServiceInterface $emitService, JobRepositoryContract $jobRepository)
    {
        $this->emitService = $emitService;
        $this->jobRepository = $jobRepository;
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
                $now = new \DateTimeImmutable();

                foreach ($processedChunks[$chunkKey] ?? [] as $jobKey) {
                    $this->jobRepository->start($jobKey, $now);
                }

                $this->emitService->emit(new TypedMappingComponentCollection($dataType, $emissionChunk));

                $now = new \DateTimeImmutable();

                foreach ($processedChunks[$chunkKey] ?? [] as $jobKey) {
                    $this->jobRepository->finish($jobKey, $now);
                }
            }
        }
    }
}
