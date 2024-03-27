<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;

/**
 * Loads available portals and portal-extensions
 */
interface PortalLoaderInterface
{
    /**
     * Finds classes for available portals and constructs objects from those classes
     */
    public function getPortals(): PortalCollection;

    /**
     * Finds classes for available portal-extensions and constructs objects from those classes
     */
    public function getPortalExtensions(): PortalExtensionCollection;
}
