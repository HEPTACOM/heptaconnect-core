<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobCollection;

abstract class JobDispatcherContract
{
    abstract public function dispatch(JobCollection $jobs): void;
}
