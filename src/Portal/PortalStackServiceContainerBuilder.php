<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;

class PortalStackServiceContainerBuilder
{
    public function build(PortalContract $portal, PortalExtensionCollection $portalExtensions): PortalStackServiceContainer
    {
        $services = $portal->getServices() + [
            'portal' => $portal,
            \get_class($portal) => $portal,
        ];

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            $services = $portalExtension->extendServices($services);

            $services['portal_extensions'][] = $portalExtension;
            $services[\get_class($portalExtension)] = $portalExtension;
        }

        return new PortalStackServiceContainer($services);
    }
}
