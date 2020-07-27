<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface PortalRegistryInterface
{
    public function getPortal(PortalNodeKeyInterface $portalNodeKey): ?PortalInterface;

    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection;
}
