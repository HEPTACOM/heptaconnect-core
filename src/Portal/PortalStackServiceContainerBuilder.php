<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PortalStackServiceContainerBuilder
{
    private LoggerInterface $logger;

    private NormalizationRegistry $normalizationRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    public function __construct(
        LoggerInterface $logger,
        NormalizationRegistry $normalizationRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking
    ) {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeKeyInterface $portalNodeKey
    ): Container {
        $containerBuilder = new ContainerBuilder();

        foreach ($this->getPathsToLoad($portal, $portalExtensions) as $path) {
            $this->loadContainerPackage($path, $containerBuilder);
        }

        $containerBuilder->set(PortalContract::class, $portal);
        $containerBuilder->set('portal_extensions', $portalExtensions);
        $containerBuilder->set(LoggerInterface::class, $this->logger);
        $containerBuilder->set(NormalizationRegistry::class, $this->normalizationRegistry);
        $containerBuilder->set(DeepCloneContract::class, new DeepCloneContract());
        $containerBuilder->set(DeepObjectIteratorContract::class, new DeepObjectIteratorContract());
        $containerBuilder->set(PortalStorageInterface::class, $this->portalStorageFactory->createPortalStorage($portalNodeKey));
        $containerBuilder->set(ResourceLockFacade::class, new ResourceLockFacade($this->resourceLocking));
        $containerBuilder->set(PortalNodeKeyInterface::class, $portalNodeKey);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    /**
     * @return iterable<string>
     */
    private function getPathsToLoad(PortalContract $portal, PortalExtensionCollection $portalExtensions): iterable
    {
        yield $portal->getPath();
        yield from $portalExtensions->map(static fn (PortalExtensionContract $ext): string => $ext->getPath());
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    private function loadContainerPackage(string $path, ContainerBuilder $containerBuilder): void
    {
        $fileLocator = new FileLocator($path);

        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        foreach (\glob($path.'/resources/config/services.{yml,yaml,xml,php}') as $serviceDefPath) {
            try {
                $delegatingLoader->load($serviceDefPath);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($serviceDefPath, $throwable);
            }
        }
    }
}
