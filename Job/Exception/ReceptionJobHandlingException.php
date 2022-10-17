<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Exception;

use Heptacom\HeptaConnect\Core\Job\JobData;

class ReceptionJobHandlingException extends \RuntimeException
{
    public function __construct(private JobData $jobData, int $code, ?\Throwable $throwable = null)
    {
        parent::__construct('Reception job could not be processed', $code, $throwable);
    }

    public function getJobData(): JobData
    {
        return $this->jobData;
    }
}
