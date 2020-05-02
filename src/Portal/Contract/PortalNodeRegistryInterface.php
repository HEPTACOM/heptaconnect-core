<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;

interface PortalNodeRegistryInterface
{
    public function getPortalNode(string $portalNodeId): ?PortalNodeInterface;
}
