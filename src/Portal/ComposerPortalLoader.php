<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\Composer\Contract\PackageConfigurationLoaderInterface;
use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration;
use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Psr\Log\LoggerInterface;

class ComposerPortalLoader
{
    private PackageConfigurationLoaderInterface $packageConfigLoader;

    private PortalFactory $portalFactory;

    private LoggerInterface $logger;

    public function __construct(
        PackageConfigurationLoaderInterface $packageConfigLoader,
        PortalFactory $portalFactory,
        LoggerInterface $logger
    ) {
        $this->packageConfigLoader = $packageConfigLoader;
        $this->portalFactory = $portalFactory;
        $this->logger = $logger;
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
                    $this->logger->critical(LogMessage::PORTAL_LOAD_ERROR(), [
                        'portal' => $portal,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    public function getPortalExtensions(): PortalExtensionCollection
    {
        $result = new PortalExtensionCollection();

        /** @var PackageConfiguration $package */
        foreach ($this->packageConfigLoader->getPackageConfigurations() as $package) {
            $portalExtensions = (array) ($package->getConfiguration()['portalExtensions'] ?? []);

            /** @var class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeExtensionInterface> $portalExtension */
            foreach ($portalExtensions as $portalExtension) {
                try {
                    $result->push([$this->portalFactory->instantiatePortalNodeExtension($portalExtension)]);
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
}
