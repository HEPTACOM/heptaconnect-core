<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Core\Exploration\Exception\PortalNodeNotFoundException;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassStringCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExploreServiceInterface
{
    /**
     * @throws PortalNodeNotFoundException
     */
    public function explore(
        PortalNodeKeyInterface $portalNodeKey,
        ?EntityTypeClassStringCollection $entityTypes = null
    ): void;

    /**
     * @throws PortalNodeNotFoundException
     */
    public function dispatchExploreJob(
        PortalNodeKeyInterface $portalNodeKey,
        ?EntityTypeClassStringCollection $entityTypes = null
    ): void;
}
