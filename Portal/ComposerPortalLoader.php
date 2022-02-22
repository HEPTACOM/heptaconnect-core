<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\Composer\Contract\PackageConfigurationLoaderInterface;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationCollection;
use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract;
use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Psr\Log\LoggerInterface;

class ComposerPortalLoader
{
    private PackageConfigurationLoaderInterface $packageConfigLoader;

    private PortalFactoryContract $portalFactory;

    private LoggerInterface $logger;

    private ?PackageConfigurationCollection $cachedPackageConfiguration = null;

    public function __construct(
        PackageConfigurationLoaderInterface $packageConfigLoader,
        PortalFactoryContract $portalFactory,
        LoggerInterface $logger
    ) {
        $this->packageConfigLoader = $packageConfigLoader;
        $this->portalFactory = $portalFactory;
        $this->logger = $logger;
    }

    public function getPortals(): PortalCollection
    {
        $portalCollection = new PortalCollection();

        /** @var PackageConfiguration $package */
        foreach ($this->getPackageConfigurationsCached() as $package) {
            $portals = (array) ($package->getConfiguration()['portals'] ?? []);

            /** @var class-string<PortalContract> $portal */
            foreach ($portals as $portal) {
                try {
                    $portalCollection->push([$this->portalFactory->instantiatePortal($portal)]);
                } catch (AbstractInstantiationException $exception) {
                    $this->logger->critical(LogMessage::PORTAL_LOAD_ERROR(), [
                        'portal' => $portal,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        return $portalCollection;
    }

    public function getPortalExtensions(): PortalExtensionCollection
    {
        $result = new PortalExtensionCollection();

        /** @var PackageConfiguration $package */
        foreach ($this->getPackageConfigurationsCached() as $package) {
            $portalExtensions = (array) ($package->getConfiguration()['portalExtensions'] ?? []);

            /** @var class-string<PortalExtensionContract> $portalExtension */
            foreach ($portalExtensions as $portalExtension) {
                try {
                    $result->push([$this->portalFactory->instantiatePortalExtension($portalExtension)]);
                } catch (AbstractInstantiationException $exception) {
                    $this->logger->critical(LogMessage::PORTAL_EXTENSION_LOAD_ERROR(), [
                        'portalExtension' => $portalExtension,
                        'exception' => $exception,
                    ]);
                }
            }
        }

        return $result;
    }

    private function getPackageConfigurationsCached(): PackageConfigurationCollection
    {
        return $this->cachedPackageConfiguration ??= $this->packageConfigLoader->getPackageConfigurations();
    }
}
