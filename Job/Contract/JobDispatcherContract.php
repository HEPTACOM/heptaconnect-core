<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobCollection;

abstract class JobDispatcherContract
{
    /**
     * Dispatch all given jobs to be run deferred.
     */
    abstract public function dispatch(JobCollection $jobs): void;
}
