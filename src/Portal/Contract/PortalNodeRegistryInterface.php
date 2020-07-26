<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalNodeExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface PortalNodeRegistryInterface
{
    public function getPortalNode(PortalNodeKeyInterface $portalNodeKey): ?PortalNodeInterface;

    public function getPortalNodeExtensions(PortalNodeKeyInterface $portalNodeKey): PortalNodeExtensionCollection;
}
