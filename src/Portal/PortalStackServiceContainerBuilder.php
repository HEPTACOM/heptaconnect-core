<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AllDefinitionDefaultsCompilerPass;
use Heptacom\HeptaConnect\Portal\Base\Builder\FlowComponent;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PortalStackServiceContainerBuilder implements PortalStackServiceContainerBuilderInterface
{
    public const STATUS_REPORTER_TAG = 'heptaconnect.flow_component.status_reporter';

    public const EMITTER_TAG = 'heptaconnect.flow_component.emitter';

    public const EMITTER_DECORATOR_TAG = 'heptaconnect.flow_component.emitter_decorator';

    public const EXPLORER_TAG = 'heptaconnect.flow_component.explorer';

    public const EXPLORER_DECORATOR_TAG = 'heptaconnect.flow_component.explorer_decorator';

    public const RECEIVER_TAG = 'heptaconnect.flow_component.receiver';

    public const RECEIVER_DECORATOR_TAG = 'heptaconnect.flow_component.receiver_decorator';

    private LoggerInterface $logger;

    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private ProfilerFactoryContract $profilerFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private FlowComponent $flowComponentBuilder;

    public function __construct(
        LoggerInterface $logger,
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        ProfilerFactoryContract $profilerFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        FlowComponent $flowComponentBuilder
    ) {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->profilerFactory = $profilerFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->flowComponentBuilder = $flowComponentBuilder;
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
        $emitterTag = self::EMITTER_TAG;
        $explorerTag = self::EXPLORER_TAG;
        $receiverTag = self::RECEIVER_TAG;

        foreach ($this->getPathsToLoad($portal, $portalExtensions) as $path => $autoloadPsr4) {
            $this->loadContainerPackage($path, $containerBuilder, $autoloadPsr4);

            /** @var Definition[] $newDefinitions */
            $newDefinitions = \array_diff_key($containerBuilder->getDefinitions(), $seenDefinitions);
            $seenDefinitions = $containerBuilder->getDefinitions();
            $this->tagDefinitionsByPriority($newDefinitions, StatusReporterContract::class, self::STATUS_REPORTER_TAG, -100 * $packageStep);
            $this->tagDefinitionsByPriority($newDefinitions, EmitterContract::class, $emitterTag, -100 * $packageStep);
            $this->tagDefinitionsByPriority($newDefinitions, ExplorerContract::class, $explorerTag, -100 * $packageStep);
            $this->tagDefinitionsByPriority($newDefinitions, ReceiverContract::class, $receiverTag, -100 * $packageStep);

            $emitterTag = self::EMITTER_DECORATOR_TAG;
            $explorerTag = self::EXPLORER_DECORATOR_TAG;
            $receiverTag = self::RECEIVER_DECORATOR_TAG;
            ++$packageStep;
        }

        $this->removeAboutToBeSyntheticlyInjectedServices($containerBuilder);
        $this->setSyntheticServices($containerBuilder, [
            PortalContract::class => $portal,
            PortalExtensionCollection::class => $portalExtensions,
            ClientInterface::class => Psr18ClientDiscovery::find(),
            RequestFactoryInterface::class => Psr17FactoryDiscovery::findRequestFactory(),
            UriFactoryInterface::class => Psr17FactoryDiscovery::findUriFactory(),
            LoggerInterface::class => $this->logger,
            NormalizationRegistryContract::class => $this->normalizationRegistry,
            DeepCloneContract::class => new DeepCloneContract(),
            DeepObjectIteratorContract::class => new DeepObjectIteratorContract(),
            PortalStorageInterface::class => $this->portalStorageFactory->createPortalStorage($portalNodeKey),
            ResourceLockFacade::class => new ResourceLockFacade($this->resourceLocking),
            PortalNodeKeyInterface::class => $portalNodeKey,
            ProfilerContract::class => $this->profilerFactory->factory('HeptaConnect\Portal::'.$this->storageKeyGenerator->serialize($portalNodeKey)),
        ]);
        $containerBuilder->setAlias(\get_class($portal), PortalContract::class);

        $containerBuilder->setDefinition(StatusReporterCollection::class, new Definition(null, [new TaggedIteratorArgument(self::STATUS_REPORTER_TAG)]));
        $containerBuilder->setDefinition(EmitterCollection::class, new Definition(null, [new TaggedIteratorArgument(self::EMITTER_TAG)]));
        $containerBuilder->setDefinition(EmitterCollection::class.'.decorator', new Definition(EmitterCollection::class, [new TaggedIteratorArgument(self::EMITTER_DECORATOR_TAG)]));
        $containerBuilder->setDefinition(ExplorerCollection::class, new Definition(null, [new TaggedIteratorArgument(self::EXPLORER_TAG)]));
        $containerBuilder->setDefinition(ExplorerCollection::class.'.decorator', new Definition(ExplorerCollection::class, [new TaggedIteratorArgument(self::EXPLORER_DECORATOR_TAG)]));
        $containerBuilder->setDefinition(ReceiverCollection::class, new Definition(null, [new TaggedIteratorArgument(self::RECEIVER_TAG)]));
        $containerBuilder->setDefinition(ReceiverCollection::class.'.decorator', new Definition(ReceiverCollection::class, [new TaggedIteratorArgument(self::RECEIVER_DECORATOR_TAG)]));

        $containerBuilder->addCompilerPass(new AllDefinitionDefaultsCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

        return $containerBuilder;
    }

    /**
     * @psalm-return iterable<string, bool>
     */
    private function getPathsToLoad(PortalContract $portal, PortalExtensionCollection $portalExtensions): iterable
    {
        yield $portal->getPath() => $portal->hasAutomaticPsr4Prototyping();

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            yield $portalExtension->getPath() => $portalExtension->hasAutomaticPsr4Prototyping();
        }
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    private function loadContainerPackage(string $path, ContainerBuilder $containerBuilder, bool $autoloadPsr4): void
    {
        $fileLocator = new FileLocator($path);
        $fileLoader = new GlobFileLoader($containerBuilder, $fileLocator);
        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        if ($autoloadPsr4) {
            foreach ($this->getPsr4NamespacesFromPackage($path) as $namespace => $directory) {
                $fileLoader->registerClasses(new Definition(), $namespace, $directory.DIRECTORY_SEPARATOR.'*');
            }
        }

        foreach (\glob($path.'/resources/config/services.{yml,yaml,xml,php}', \GLOB_BRACE) as $serviceDefPath) {
            try {
                $delegatingLoader->load($serviceDefPath);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($serviceDefPath, $throwable);
            }
        }

        $this->loadFlowComponentsFromBuilder($containerBuilder, $path);
    }

    private function loadFlowComponentsFromBuilder(ContainerBuilder $containerBuilder, string $path): void
    {
        foreach (\glob($path . '/flow-components/*.php', \GLOB_BRACE) as $flowComponentScript) {
            // prevent access to object context
            (static function (string $file) {
                include $file;
            })($flowComponentScript);
        }

        foreach ($this->flowComponentBuilder->buildExplorers() as $explorer) {
            $this->setSyntheticServices($containerBuilder, [
                \bin2hex(random_bytes(16)) => $explorer,
            ]);
        }

        foreach ($this->flowComponentBuilder->buildEmitters() as $emitter) {
            $this->setSyntheticServices($containerBuilder, [
                \bin2hex(random_bytes(16)) => $emitter,
            ]);
        }

        foreach ($this->flowComponentBuilder->buildReceivers() as $receiver) {
            $this->setSyntheticServices($containerBuilder, [
                \bin2hex(random_bytes(16)) => $receiver,
            ]);
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
