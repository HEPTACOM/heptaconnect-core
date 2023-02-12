<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Exception;

use Heptacom\HeptaConnect\Core\Job\JobData;

class ReceptionJobHandlingException extends \RuntimeException
{
    private JobData $jobData;

    public function __construct(JobData $jobData, int $code, ?\Throwable $throwable = null)
    {
        parent::__construct('Reception job could not be processed', $code, $throwable);
        $this->jobData = $jobData;
    }

    public function getJobData(): JobData
    {
        return $this->jobData;
    }
}
