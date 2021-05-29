<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Symfony\Component\Messenger\MessageBusInterface;

class JobDispatcher extends JobDispatcherContract
{
    private MessageBusInterface $bus;

    private JobRepositoryContract $jobRepository;

    private JobPayloadRepositoryContract $jobPayloadRepository;

    public function __construct(
        MessageBusInterface $bus,
        JobRepositoryContract $jobRepository,
        JobPayloadRepositoryContract $jobPayloadRepository
    ) {
        $this->bus = $bus;
        $this->jobRepository = $jobRepository;
        $this->jobPayloadRepository = $jobPayloadRepository;
    }

    public function dispatch(JobCollection $jobs): void
    {
        $jobKeys = [];

        foreach ($jobs as $job) {
            $payload = $job->getPayload();
            $payloadId = $payload === null ? null : $this->jobPayloadRepository->add($payload);
            $jobKeys[] = $this->jobRepository->add($job->getMappingComponent(), $job->getType(), $payloadId);
        }

        if ($jobKeys !== []) {
            return;
        }

        $message = new JobMessage();
        $message->getJobKeys()->push($jobKeys);
        $this->bus->dispatch($message);
    }
}
