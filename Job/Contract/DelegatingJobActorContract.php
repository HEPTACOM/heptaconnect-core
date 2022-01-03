<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

abstract class DelegatingJobActorContract
{
    abstract public function performJobs(string $type, JobDataCollection $jobs): void;
}
