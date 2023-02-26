<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Schedule\JobSchedulePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobScheduleActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Job\JobSchedule\JobScheduleCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Job\JobSchedule\JobScheduleResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Job\JobScheduleUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\JobsMissingException;
use Symfony\Component\Messenger\MessageBusInterface;

final class JobScheduleUi implements JobScheduleUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private MessageBusInterface $messageBus,
        private JobGetActionInterface $jobGet,
        private JobScheduleActionInterface $jobScheduleAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(JobScheduleUiActionInterface::class);
    }

    public function schedule(JobScheduleCriteria $criteria, UiActionContextInterface $context): JobScheduleResult
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $foundJobKeys = new JobKeyCollection();

        /** @var JobGetResult $jobGet */
        foreach ($this->jobGet->get(new JobGetCriteria($criteria->getJobKeys())) as $jobGet) {
            $foundJobKeys->push([$jobGet->getJobKey()]);
        }

        $jobsNotFound = $criteria->getJobKeys()->filter(
            static fn (JobKeyInterface $jobKey): bool => !$foundJobKeys->contains($jobKey)
        );

        if (!$jobsNotFound->isEmpty()) {
            throw $trail->throwable(new JobsMissingException($jobsNotFound, 1677424700));
        }

        $startResult = $this->jobScheduleAction->schedule(new JobSchedulePayload($foundJobKeys, new \DateTimeImmutable(), 'UI schedule'));

        if (!$startResult->getScheduledJobs()->isEmpty()) {
            $message = new JobMessage();
            $message->getJobKeys()->push($startResult->getScheduledJobs());
            $this->messageBus->dispatch($message);
        }

        return $trail->return(new JobScheduleResult($startResult->getScheduledJobs(), $startResult->getSkippedJobs()));
    }
}
