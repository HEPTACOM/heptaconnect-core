<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface PortalRegistryInterface
{
    public function getPortal(PortalNodeKeyInterface $portalNodeKey): PortalContract;

    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection;
}
