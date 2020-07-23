<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Explore\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;

interface ExploreServiceInterface
{
    public function explore(PortalNodeKeyInterface $portalNodeKey): void;
}
