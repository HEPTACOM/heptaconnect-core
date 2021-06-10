<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MessageHandler implements MessageSubscriberInterface
{
    private JobRepositoryContract $jobRepository;

    private JobPayloadRepositoryContract $jobPayloadRepository;

    private DelegatingJobActorContract $jobActor;

    private MessageBusInterface $bus;

    public function __construct(
        JobRepositoryContract $jobRepository,
        JobPayloadRepositoryContract $jobPayloadRepository,
        DelegatingJobActorContract $jobActor,
        MessageBusInterface $bus
    ) {
        $this->jobRepository = $jobRepository;
        $this->jobPayloadRepository = $jobPayloadRepository;
        $this->jobActor = $jobActor;
        $this->bus = $bus;
    }

    public static function getHandledMessages(): iterable
    {
        yield JobMessage::class => ['method' => 'handleJob'];
    }

    public function handleJob(JobMessage $message): void
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
            if (!$this->jobActor->performJob($job->getJobType(), $job->getMapping(), $payload)) {
                $this->bus->dispatch(Envelope::wrap($message)->with(new DelayStamp(60000)));
            }
            // TODO mark as executed
        }
    }
}
