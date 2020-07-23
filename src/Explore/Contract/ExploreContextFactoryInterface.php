<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Explore\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;

interface ExploreContextFactoryInterface
{
    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface;
}
