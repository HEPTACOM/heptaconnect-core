<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\Composer\Contract\PackageConfigurationLoaderInterface;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationCollection;
use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract;
use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\InvalidSubtypeClassNameException;
use Heptacom\HeptaConnect\Dataset\Base\Exception\UnexpectedLeadingNamespaceSeparatorInClassNameException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalType;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
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

            foreach ($portals as $portal) {
                try {
                    $portalCollection->push([$this->portalFactory->instantiatePortal(new PortalType($portal))]);
                } catch (AbstractInstantiationException|InvalidSubtypeClassNameException|InvalidClassNameException $exception) {
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

        foreach ($this->getPackageConfigurationsCached() as $package) {
            $portalExtensions = (array) ($package->getConfiguration()['portalExtensions'] ?? []);

            /** @var class-string<PortalExtensionContract> $portalExtension */
            foreach ($portalExtensions as $portalExtension) {
                try {
                    $result->push([$this->portalFactory->instantiatePortalExtension(
                        new PortalExtensionType($portalExtension)
                    )]);
                } catch (AbstractInstantiationException|InvalidSubtypeClassNameException|InvalidClassNameException|UnexpectedLeadingNamespaceSeparatorInClassNameException $exception) {
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
