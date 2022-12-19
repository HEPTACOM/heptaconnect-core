<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message\JobMessage;
use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class JobDispatcher extends JobDispatcherContract
{
    public function __construct(
        private MessageBusInterface $bus,
        private JobCreateActionInterface $jobCreateAction
    ) {
    }

    public function dispatch(JobCollection $jobs): void
    {
        $createPayload = new JobCreatePayloads($jobs->map(
            static fn (JobContract $j): JobCreatePayload => new JobCreatePayload($j->getType(), $j->getMappingComponent(), $j->getPayload())
        ));
        $createResults = $this->jobCreateAction->create($createPayload);

        if ($createResults->isEmpty()) {
            return;
        }

        $message = new JobMessage();
        $message->getJobKeys()->push($createResults->map(
            static fn (JobCreateResult $createResult): JobKeyInterface => $createResult->getJobKey()
        ));
        $this->bus->dispatch($message);
    }
}
