<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;


use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

interface EmissionHandlerInterface
{
    public function triggerEmission(JobDataCollection $jobs): void;
}
