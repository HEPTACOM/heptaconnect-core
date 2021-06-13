<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AllDefinitionDefaultsCompilerPass;
use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PortalStackServiceContainerBuilder
{
    public const STATUS_REPORTER_TAG = 'heptaconnect.flow_component.status_reporter';

    private LoggerInterface $logger;

    private NormalizationRegistry $normalizationRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private ProfilerFactoryContract $profilerFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        LoggerInterface $logger,
        NormalizationRegistry $normalizationRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        ProfilerFactoryContract $profilerFactory,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->profilerFactory = $profilerFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeKeyInterface $portalNodeKey
    ): ContainerBuilder {
        $containerBuilder = new ContainerBuilder();

        $seenDefinitions = [];
        $packageStep = 0;

        foreach ($this->getPathsToLoad($portal, $portalExtensions) as $path) {
            $this->loadContainerPackage($path, $containerBuilder);

            /** @var Definition[] $newDefinitions */
            $newDefinitions = \array_diff_key($containerBuilder->getDefinitions(), $seenDefinitions);
            $seenDefinitions = $containerBuilder->getDefinitions();
            $this->tagDefinitionsByPriority($newDefinitions, StatusReporterContract::class, self::STATUS_REPORTER_TAG, -100 * $packageStep);
            ++$packageStep;
        }

        $this->removeAboutToBeSyntheticlyInjectedServices($containerBuilder);
        $this->setSyntheticServices($containerBuilder, [
            PortalContract::class => $portal,
            PortalExtensionCollection::class => $portalExtensions,
            LoggerInterface::class => $this->logger,
            NormalizationRegistry::class => $this->normalizationRegistry,
            DeepCloneContract::class => new DeepCloneContract(),
            DeepObjectIteratorContract::class => new DeepObjectIteratorContract(),
            PortalStorageInterface::class => $this->portalStorageFactory->createPortalStorage($portalNodeKey),
            ResourceLockFacade::class => new ResourceLockFacade($this->resourceLocking),
            PortalNodeKeyInterface::class => $portalNodeKey,
            ProfilerContract::class => $this->profilerFactory->factory('HeptaConnect\Portal::'.$this->storageKeyGenerator->serialize($portalNodeKey)),
        ]);
        $containerBuilder->setAlias(\get_class($portal), PortalContract::class);

        $containerBuilder->setDefinition(StatusReporterCollection::class, new Definition(null, [new TaggedIteratorArgument(self::STATUS_REPORTER_TAG)]));

        $containerBuilder->addCompilerPass(new AllDefinitionDefaultsCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

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
        $fileLoader = new GlobFileLoader($containerBuilder, $fileLocator);
        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        foreach ($this->getPsr4NamespacesFromPackage($path) as $namespace => $directory) {
            $fileLoader->registerClasses(new Definition(), $namespace, $directory.DIRECTORY_SEPARATOR.'*');
        }

        foreach (\glob($path.'/resources/config/services.{yml,yaml,xml,php}') as $serviceDefPath) {
            try {
                $delegatingLoader->load($serviceDefPath);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($serviceDefPath, $throwable);
            }
        }
    }

    /**
     * @param Definition[] $definitions
     * @psalm-param class-string $interface
     */
    private function tagDefinitionsByPriority(array $definitions, string $interface, string $tag, int $priority): void
    {
        foreach ($definitions as $id => $definition) {
            $class = $definition->getClass() ?? (string)$id;

            if (!\class_exists($class) || !\is_a($class, $interface, true)) {
                continue;
            }

            $definition->clearTag($tag);
            $definition->addTag($tag, ['priority' => $priority]);
        }
    }

    private function getPsr4NamespacesFromPackage(string $path): array
    {
        $composerJsonFile = $path.DIRECTORY_SEPARATOR.'composer.json';

        if (\is_file($composerJsonFile)) {
            $composerContent = \file_get_contents($composerJsonFile);
            $composerJson = (array) \json_decode($composerContent, true, 512, \JSON_THROW_ON_ERROR);

            return (array) ($composerJson['autoload']['psr-4'] ?? []);
        }

        return [];
    }

    private function removeAboutToBeSyntheticlyInjectedServices(ContainerBuilder $containerBuilder): void
    {
        $automaticLoadedDefinitionsToRemove = [];

        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? (string)$id;

            if (!\class_exists($class)) {
                continue;
            }

            if (\is_a($class, PortalContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = (string)$id;
            }

            if (\is_a($class, PortalExtensionContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = (string)$id;
            }
        }

        \array_walk($automaticLoadedDefinitionsToRemove, [$containerBuilder, 'removeDefinition']);
    }

    /**
     * @param object[] $services
     */
    private function setSyntheticServices(ContainerBuilder $containerBuilder, array $services): void
    {
        foreach ($services as $id => $service) {
            $definitionId = (string)$id;
            $containerBuilder->set($definitionId, $service);
            $definition = (new Definition())
                ->setSynthetic(true)
                ->setClass(\get_class($service));
            $containerBuilder->setDefinition($definitionId, $definition);
        }
    }
}
