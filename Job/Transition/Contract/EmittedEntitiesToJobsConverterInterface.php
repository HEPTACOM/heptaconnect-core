<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Transition\Contract;

use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmittedEntitiesToJobsConverterInterface
{
    /**
     * Converts freshly emitted entities into jobs like receive jobs.
     *
     * @param DatasetEntityCollection<DatasetEntityContract> $entities
     */
    public function convert(PortalNodeKeyInterface $portalNodeKey, DatasetEntityCollection $entities): JobCollection;
}
