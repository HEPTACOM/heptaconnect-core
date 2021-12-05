<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Repository\JobAdd;
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
        $payloads = [];

        foreach ($jobs as $jobKey => $job) {
            $payload = $job->getPayload();

            if (\is_array($payload)) {
                $payloads[$jobKey] = $payload;
            }
        }

        $payloadKeys = $this->jobPayloadRepository->add($payloads);
        $jobAdds = [];

        /** @var JobContract $job */
        foreach ($jobs as $jobKey => $job) {
            $jobAdds[$jobKey] = new JobAdd($job->getType(), $job->getMappingComponent(), $payloadKeys[$jobKey] ?? null);
        }

        $jobKeys = \array_values($this->jobRepository->add($jobAdds));

        if ($jobKeys === []) {
            return;
        }

        $message = new JobMessage();
        $message->getJobKeys()->push($jobKeys);
        $this->bus->dispatch($message);
    }
}
