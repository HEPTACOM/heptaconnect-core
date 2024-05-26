<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Utility\Collection\AbstractObjectCollection;

/**
 * @extends AbstractObjectCollection<JobData>
 */
class JobDataCollection extends AbstractObjectCollection
{
    #[\Override]
    protected function getT(): string
    {
        return JobData::class;
    }
}
