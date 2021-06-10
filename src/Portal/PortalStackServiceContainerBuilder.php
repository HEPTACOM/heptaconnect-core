<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Portal\Exception\PortalClassReflectionException;
use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;


class PortalStackServiceContainerBuilder
{
    private LoggerInterface $logger;
    private NormalizationRegistry $normalizationRegistry;

    public function __construct(LoggerInterface $logger, NormalizationRegistry $normalizationRegistry)
    {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
    }

    /**
     * @throws PortalClassReflectionException
     * @throws DelegatingLoaderLoadException
     */
    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeContextInterface $context
    ): Container {
        $containerBuilder = new ContainerBuilder();
        $className = get_class($portal);
        try {
            $reflector = new \ReflectionClass($className);
        } catch (\Throwable $throwable) {
            throw new PortalClassReflectionException($className);
        }
        $serviceConfigPath = dirname($reflector->getFileName()) . '\\..';
        $fileLocator = new FileLocator($serviceConfigPath);

        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        foreach (glob($serviceConfigPath . '/resources/config/services.*') as $path) {
            try {
                $delegatingLoader->load($path);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($path);
            }
        }

        $containerBuilder->set('portal', $portal);
        $containerBuilder->set('portal_extensions', $portalExtensions);
        $containerBuilder->set(LoggerInterface::class, $this->logger);
        $containerBuilder->set(NormalizationRegistry::class, $this->normalizationRegistry);
        $containerBuilder->set(DeepCloneContract::class, new DeepCloneContract());
        $containerBuilder->set(DeepObjectIteratorContract::class, new DeepObjectIteratorContract());
        $containerBuilder->set(PortalNodeContextInterface::class, $context);
        $containerBuilder->set(PortalStorageInterface::class, $context->getStorage());
        $containerBuilder->set(ResourceLockFacade::class, $context->getResourceLocker());

        $containerBuilder->compile();
        $portal->setContainer($containerBuilder);

        return $containerBuilder;
    }
}
