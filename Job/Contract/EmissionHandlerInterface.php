<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

interface EmissionHandlerInterface
{
    /**
     * Unpack emission job data into further processable data and start processing of the unpacked job data.
     */
    public function triggerEmission(JobDataCollection $jobs): void;
}
