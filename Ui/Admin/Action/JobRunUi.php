<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Job\JobRun\JobRunPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Job\JobRunUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\JobMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\JobProcessingException;

final class JobRunUi implements JobRunUiActionInterface
{
    private DelegatingJobActorContract $jobActor;

    private JobGetActionInterface $jobGetAction;

    public function __construct(DelegatingJobActorContract $jobActor, JobGetActionInterface $jobGetAction)
    {
        $this->jobActor = $jobActor;
        $this->jobGetAction = $jobGetAction;
    }

    public function run(JobRunPayload $payload): void
    {
        /** @var array<string, JobDataCollection> $jobDatasByType */
        $jobDatasByType = [];
        $foundJobKeys = new JobKeyCollection();

        /** @var JobGetResult $jobGet */
        foreach ($this->jobGetAction->get(new JobGetCriteria($payload->getJobKeys())) as $jobGet) {
            $foundJobKeys->push([$jobGet->getJobKey()]);

            $jobData = new JobData($jobGet->getMappingComponent(), $jobGet->getPayload(), $jobGet->getJobKey());

            $jobs = $jobDatasByType[$jobGet->getJobType()] ??= new JobDataCollection();
            $jobs->push([$jobData]);
        }

        foreach ($payload->getJobKeys() as $jobKey) {
            if (!$foundJobKeys->contains($jobKey)) {
                throw new JobMissingException($jobKey, 1659721163);
            }
        }

        $alreadyRunJobKeys = new JobKeyCollection();

        while ($jobDatasByType !== []) {
            $jobType = (string) \key($jobDatasByType);
            $jobs = $jobDatasByType[$jobType];
            unset($jobDatasByType[$jobType]);

            try {
                $this->jobActor->performJobs($jobType, $jobs);
            } catch (\Throwable $exception) {
                $notYetPerformedJobs = new JobKeyCollection();

                foreach ($jobDatasByType as $missingJobs) {
                    $notYetPerformedJobs->push($missingJobs->column('getJobKey'));
                }

                $justTriedJobKeys = new JobKeyCollection($jobs->column('getJobKey'));

                throw new JobProcessingException($alreadyRunJobKeys, $justTriedJobKeys, $notYetPerformedJobs, 1659721164);
            }

            $alreadyRunJobKeys->push($jobs->column('getJobKey'));
        }
    }
}
