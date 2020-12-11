<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;

class PortalStackServiceContainerBuilder
{
    public function build(PortalContract $portal, PortalExtensionCollection $portalExtensions): PortalStackServiceContainer
    {
        $services = $portal->getServices();

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            $services = $portalExtension->extendServices($services);
        }

        return new PortalStackServiceContainer($services);
    }
}
