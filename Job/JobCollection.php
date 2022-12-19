<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Dataset\Base\Support\AbstractCollection;

/**
 * @extends AbstractCollection<JobContract>
 */
class JobCollection extends AbstractCollection
{
    protected function isValidItem(mixed $item): bool
    {
        /* @phpstan-ignore-next-line */
        return $item instanceof JobContract;
    }
}
