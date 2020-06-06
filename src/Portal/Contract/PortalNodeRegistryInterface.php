<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\StoragePortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\PortalNodeExtensionCollection;

interface PortalNodeRegistryInterface
{
    public function getPortalNode(StoragePortalNodeKeyInterface $portalNodeKey): ?PortalNodeInterface;

    public function getPortalNodeExtensions(StoragePortalNodeKeyInterface $portalNodeKey): PortalNodeExtensionCollection;
}
