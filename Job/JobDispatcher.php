<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Create\JobCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Create\JobCreatePayloads;
use Symfony\Component\Messenger\MessageBusInterface;

class JobDispatcher extends JobDispatcherContract
{
    private MessageBusInterface $bus;

    private JobCreateActionInterface $jobCreateAction;

    public function __construct(MessageBusInterface $bus, JobCreateActionInterface $jobCreateAction)
    {
        $this->bus = $bus;
        $this->jobCreateAction = $jobCreateAction;
    }

    public function dispatch(JobCollection $jobs): void
    {
        $createPayload = new JobCreatePayloads($jobs->map(
            static fn (JobContract $j): JobCreatePayload =>
                new JobCreatePayload($j->getType(), $j->getMappingComponent(), $j->getPayload())
        ));
        $createResult = $this->jobCreateAction->create($createPayload);

        if ($createResult->count() === 0) {
            return;
        }

        $message = new JobMessage();
        $message->getJobKeys()->push($createResult->column('getJobKey'));
        $this->bus->dispatch($message);
    }
}
