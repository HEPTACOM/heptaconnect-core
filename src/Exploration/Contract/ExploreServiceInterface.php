<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExploreServiceInterface
{
    public function explore(PortalNodeKeyInterface $portalNodeKey): void;
}
