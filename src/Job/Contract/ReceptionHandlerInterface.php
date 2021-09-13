<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

interface ReceptionHandlerInterface
{
    public function triggerReception(JobDataCollection $jobs): void;
}
