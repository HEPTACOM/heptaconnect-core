<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\JobData;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

final class MessageHandler implements MessageSubscriberInterface
{
    private JobGetActionInterface $jobGetAction;

    private DelegatingJobActorContract $jobActor;

    private LoggerInterface $logger;

    public function __construct(
        JobGetActionInterface $jobGetAction,
        DelegatingJobActorContract $jobActor,
        LoggerInterface $logger
    ) {
        $this->jobGetAction = $jobGetAction;
        $this->jobActor = $jobActor;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        yield JobMessage::class => ['method' => 'handleJob'];
    }

    public function handleJob(JobMessage $message): void
    {
        /** @var JobDataCollection[] $jobs */
        $jobs = [];

        try {
            foreach ($this->jobGetAction->get(new JobGetCriteria($message->getJobKeys())) as $job) {
                $jobs[$job->getJobType()] ??= new JobDataCollection();
                $jobs[$job->getJobType()]->push([new JobData($job->getMappingComponent(), $job->getPayload(), $job->getJobKey())]);
            }
        } catch (\Throwable $throwable) {
            $this->logger->emergency('Jobs can not be loaded to be processed', [
                'jobKeys' => $message->getJobKeys()->asArray(),
                'exception' => $throwable,
                'code' => 1647396033,
            ]);
        }

        foreach ($jobs as $type => $jobData) {
            try {
                $this->jobActor->performJobs($type, $jobData);
            } catch (\Throwable $throwable) {
                $this->logger->emergency('Jobs can not be processed', [
                    'type' => $type,
                    'exception' => $throwable,
                    'jobData' => \iterable_to_array($jobData->column('getJobKey')),
                    'code' => 1647396034,
                ]);
            }
        }
    }
}
