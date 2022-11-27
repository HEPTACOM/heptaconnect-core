<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExploreContextFactoryInterface
{
    /**
     * Create a context for an exploration on the given portal node.
     */
    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface;
}
