<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;

interface PortalLoaderInterface
{
    public function getPortals(): PortalCollection;

    public function getPortalExtensions(): PortalExtensionCollection;
}
