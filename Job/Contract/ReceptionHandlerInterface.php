<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\Exception\ReceptionJobHandlingException;
use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

interface ReceptionHandlerInterface
{
    /**
     * Unpack reception job data into further processable data and start processing of the unpacked job data.
     *
     * @throws ReceptionJobHandlingException
     */
    public function triggerReception(JobDataCollection $jobs): void;
}
