<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Core\Job\JobDataCollection;

interface ExplorationHandlerInterface
{
    public function triggerExplorations(JobDataCollection $jobs): void;
}
