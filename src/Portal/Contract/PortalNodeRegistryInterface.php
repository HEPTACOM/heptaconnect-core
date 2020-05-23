<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StoragePortalNodeKeyInterface;

interface PortalNodeRegistryInterface
{
    public function getPortalNode(StoragePortalNodeKeyInterface $portalNodeId): ?PortalNodeInterface;
}
