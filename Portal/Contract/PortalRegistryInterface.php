<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * Service to provide instances of portals and portal extensions.
 */
interface PortalRegistryInterface
{
    /**
     * Get @see PortalContract instance by a portal node key.
     */
    public function getPortal(PortalNodeKeyInterface $portalNodeKey): PortalContract;

    /**
     * Get @see PortalExtensionContract instances that can be used on the portal node and are set to be active.
     */
    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection;
}
