<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Dataset\Base\Support\AbstractObjectCollection;

/**
 * @extends AbstractObjectCollection<JobData>
 */
class JobDataCollection extends AbstractObjectCollection
{
    protected function getT(): string
    {
        return JobData::class;
    }
}
