<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Job\JobRun\JobRunPayload;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Job\JobRunUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\JobsMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\JobProcessingException;

final class JobRunUi implements JobRunUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private DelegatingJobActorContract $jobActor,
        private JobGetActionInterface $jobGetAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(JobRunUiActionInterface::class);
    }

    public function run(JobRunPayload $payload, UiActionContextInterface $context): void
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$payload, $context]);

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

        $jobsNotFound = $payload->getJobKeys()->filter(
            static fn (JobKeyInterface $jobKey): bool => !$foundJobKeys->contains($jobKey)
        );

        if (!$jobsNotFound->isEmpty()) {
            throw $trail->throwable(new JobsMissingException($jobsNotFound, 1659721163));
        }

        $alreadyRunJobKeys = new JobKeyCollection();

        while ($jobDatasByType !== []) {
            $jobType = \key($jobDatasByType);
            $jobs = $jobDatasByType[$jobType];
            unset($jobDatasByType[$jobType]);

            try {
                $this->jobActor->performJobs($jobType, $jobs);
            } catch (\Throwable) {
                $notYetPerformedJobs = new JobKeyCollection();

                foreach ($jobDatasByType as $missingJobs) {
                    $notYetPerformedJobs->push($missingJobs->column('getJobKey'));
                }

                $justTriedJobKeys = new JobKeyCollection($jobs->column('getJobKey'));

                throw $trail->throwable(new JobProcessingException($alreadyRunJobKeys, $justTriedJobKeys, $notYetPerformedJobs, 1659721164));
            }

            $alreadyRunJobKeys->push($jobs->column('getJobKey'));
        }

        $trail->end();
    }
}
