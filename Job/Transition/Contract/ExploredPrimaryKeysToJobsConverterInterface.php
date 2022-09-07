<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Transition\Contract;

use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExploredPrimaryKeysToJobsConverterInterface
{
    /**
     * Converts freshly explorer primary keys into jobs like emit jobs.
     */
    public function convert(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType,
        StringCollection $primaryKeys
    ): JobCollection;
}
