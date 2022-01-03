<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Get\JobGetActionInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class MessageHandler implements MessageSubscriberInterface
{
    private JobGetActionInterface $jobGetAction;

    private DelegatingJobActorContract $jobActor;

    public function __construct(JobGetActionInterface $jobGetAction, DelegatingJobActorContract $jobActor)
    {
        $this->jobGetAction = $jobGetAction;
        $this->jobActor = $jobActor;
    }

    public static function getHandledMessages(): iterable
    {
        yield JobMessage::class => ['method' => 'handleJob'];
    }

    public function handleJob(JobMessage $message): void
    {
        /** @var JobDataCollection[] $jobs */
        $jobs = [];

        foreach ($this->jobGetAction->get(new JobGetCriteria($message->getJobKeys())) as $job) {
            $jobs[$job->getJobType()] ??= new JobDataCollection();
            $jobs[$job->getJobType()]->push([new JobData($job->getMappingComponent(), $job->getPayload(), $job->getJobKey())]);
        }

        foreach ($jobs as $type => $jobData) {
            $this->jobActor->performJobs($type, $jobData);
        }
    }
}
