<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\Composer\Contract\PackageConfigurationLoaderInterface;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration;
use Heptacom\HeptaConnect\Portal\Base\PortalNodeExtensionCollection;

class ComposerPortalLoader
{
    private PackageConfigurationLoaderInterface $packageConfigLoader;

    private PortalFactory $portalFactory;

    public function __construct(PackageConfigurationLoaderInterface $packageConfigLoader, PortalFactory $portalFactory)
    {
        $this->packageConfigLoader = $packageConfigLoader;
        $this->portalFactory = $portalFactory;
    }

    /**
     * @return iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface>
     */
    public function getPortals(): iterable
    {
        /** @var PackageConfiguration $package */
        foreach ($this->packageConfigLoader->getPackageConfigurations() as $package) {
            $portals = $package->getConfiguration()['portals'] ?? [];

            foreach ($portals as $portal) {
                yield $this->portalFactory->instantiatePortalNode($portal);
            }
        }
    }

    public function getPortalExtensions(): PortalNodeExtensionCollection
    {
        $result = new PortalNodeExtensionCollection();

        /** @var PackageConfiguration $package */
        foreach ($this->packageConfigLoader->getPackageConfigurations() as $package) {
            $portals = $package->getConfiguration()['portalExtensions'] ?? [];

            foreach ($portals as $portal) {
                $result->push([$this->portalFactory->instantiatePortalNodeExtension($portal)]);
            }
        }

        return $result;
    }
}
