<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class MessageHandler implements MessageSubscriberInterface
{
    private JobRepositoryContract $jobRepository;

    private JobPayloadRepositoryContract $jobPayloadRepository;

    private DelegatingJobActorContract $jobActor;

    public function __construct(
        JobRepositoryContract $jobRepository,
        JobPayloadRepositoryContract $jobPayloadRepository,
        DelegatingJobActorContract $jobActor
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobPayloadRepository = $jobPayloadRepository;
        $this->jobActor = $jobActor;
    }

    public static function getHandledMessages(): iterable
    {
        yield JobMessage::class => ['method' => 'handleJob'];
    }

    public function handleJob(JobMessage $message)
    {
        /** @var JobKeyInterface $jobKey */
        foreach ($message->getJobKeys() as $jobKey) {
            try {
                $job = $this->jobRepository->get($jobKey);
                $payload = $job->getPayloadKey() !== null ? $this->jobPayloadRepository->get($job->getPayloadKey()) : null;
            } catch (\Throwable $exception) {
                // TODO log
                continue;
            }

            // TODO mark as tried to execute
            $this->jobActor->performJob($job->getJobType(), $job->getMapping(), $payload);
            // TODO mark as executed
        }
    }
}
