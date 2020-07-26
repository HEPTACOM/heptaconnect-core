<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\Composer\Contract\PackageConfigurationLoaderInterface;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration;
use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalNodeExtensionCollection;

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
     * @return iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface>
     */
    public function getPortals(): iterable
    {
        /** @var PackageConfiguration $package */
        foreach ($this->packageConfigLoader->getPackageConfigurations() as $package) {
            $portals = (array) ($package->getConfiguration()['portals'] ?? []);

            /** @var class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface> $portal */
            foreach ($portals as $portal) {
                try {
                    yield $this->portalFactory->instantiatePortalNode($portal);
                } catch (AbstractInstantiationException $exception) {
                    // TODO log
                }
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
