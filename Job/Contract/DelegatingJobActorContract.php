<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

abstract class DelegatingJobActorContract
{
    /**
     * Invokes the underlying actions for jobs of a same type with multiple payloads, that must match the expected structure of the type.
     */
    abstract public function performJobs(string $type, JobDataCollection $jobs): void;
}
