<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddPortalConfigurationBindingsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AllDefinitionDefaultsCompilerPass;
use Heptacom\HeptaConnect\Core\Storage\Filesystem\FilesystemFactory;
use Heptacom\HeptaConnect\Portal\Base\Builder\FlowComponent;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
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
use League\Flysystem\FilesystemInterface;
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

    public const SERVICE_FROM_A_PORTAL_TAG = 'heptaconnect.service_from_a_portal';

    private LoggerInterface $logger;

    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private ProfilerFactoryContract $profilerFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private FlowComponent $flowComponentBuilder;

    private FilesystemFactory $filesystemFactory;

    private ConfigurationServiceInterface $configurationService;

    public function __construct(
        LoggerInterface $logger,
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        ProfilerFactoryContract $profilerFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        FlowComponent $flowComponentBuilder,
        FilesystemFactory $filesystemFactory,
        ConfigurationServiceInterface $configurationService
    ) {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->profilerFactory = $profilerFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->flowComponentBuilder = $flowComponentBuilder;
        $this->filesystemFactory = $filesystemFactory;
        $this->configurationService = $configurationService;
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

        /** @var PortalContract|PortalExtensionContract $package */
        foreach ([$portal, ...$portalExtensions] as $package) {
            $containerConfigurationPath = $package->getContainerConfigurationPath();
            $flowComponentsPath = $package->getFlowComponentsPath();

            $this->registerPsr4Prototype($containerBuilder, $package->getPsr4(), [
                $containerConfigurationPath,
                $flowComponentsPath,
            ]);
            $this->registerContainerConfiguration($containerBuilder, $containerConfigurationPath);
            $this->registerFlowComponentsFromBuilder($containerBuilder, $flowComponentsPath);

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

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->addTag(self::SERVICE_FROM_A_PORTAL_TAG);
        }

        $configuration = [];

        try {
            $configuration = $this->configurationService->getPortalNodeConfiguration($portalNodeKey);
        } catch (\Throwable $throwable) {
            $this->logger->error(LogMessage::PORTAL_NODE_CONFIGURATION_INVALID(), [
                'portal_node_key' => $portalNodeKey,
                'exception' => $throwable,
            ]);
        }

        $portalConfiguration = new PortalConfiguration($configuration);

        $this->removeAboutToBeSyntheticlyInjectedServices($containerBuilder);
        $this->setSyntheticServices($containerBuilder, [
            PortalContract::class => $portal,
            PortalExtensionCollection::class => $portalExtensions,
            ClientInterface::class => Psr18ClientDiscovery::find(),
            RequestFactoryInterface::class => Psr17FactoryDiscovery::findRequestFactory(),
            UriFactoryInterface::class => Psr17FactoryDiscovery::findUriFactory(),
            LoggerInterface::class => new PortalLogger(
                $this->logger,
                \sprintf('[%s] ', $this->storageKeyGenerator->serialize($portalNodeKey)),
                [
                    'portalNodeKey' => $portalNodeKey,
                ]
            ),
            NormalizationRegistryContract::class => $this->normalizationRegistry,
            DeepCloneContract::class => new DeepCloneContract(),
            DeepObjectIteratorContract::class => new DeepObjectIteratorContract(),
            PortalStorageInterface::class => $this->portalStorageFactory->createPortalStorage($portalNodeKey),
            ResourceLockFacade::class => new ResourceLockFacade($this->resourceLocking),
            PortalNodeKeyInterface::class => $portalNodeKey,
            ProfilerContract::class => $this->profilerFactory->factory('HeptaConnect\Portal::'.$this->storageKeyGenerator->serialize($portalNodeKey)),
            FilesystemInterface::class => $this->filesystemFactory->factory($portalNodeKey),
            ConfigurationContract::class => $portalConfiguration,
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
        $containerBuilder->addCompilerPass(new AddPortalConfigurationBindingsCompilerPass($portalConfiguration), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

        return $containerBuilder;
    }

    /**
     * @param Definition[] $definitions
     * @psalm-param class-string $interface
     */
    private function tagDefinitionsByPriority(array $definitions, string $interface, string $tag, int $priority): void
    {
        foreach ($definitions as $id => $definition) {
            $class = $definition->getClass() ?? (string) $id;

            if (!\class_exists($class) || !\is_a($class, $interface, true)) {
                continue;
            }

            $definition->clearTag($tag);
            $definition->addTag($tag, ['priority' => $priority]);
        }
    }

    private function registerPsr4Prototype(
        ContainerBuilder $containerBuilder,
        array $psr4,
        array $exclude = []
    ): void {
        foreach ($psr4 as $namespace => $path) {
            $fileLocator = new FileLocator($path);
            $fileLoader = new GlobFileLoader($containerBuilder, $fileLocator);

            $fileLoader->registerClasses(
                new Definition(),
                $namespace,
                \rtrim($path, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.'*',
                $exclude
            );
        }
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    private function registerContainerConfiguration(
        ContainerBuilder $containerBuilder,
        string $containerConfigurationPath
    ): void {
        $fileLocator = new FileLocator($containerConfigurationPath);
        $loaderResolver = new LoaderResolver([
            new XmlFileLoader($containerBuilder, $fileLocator),
            new YamlFileLoader($containerBuilder, $fileLocator),
            new PhpFileLoader($containerBuilder, $fileLocator),
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        $globPattern = $containerConfigurationPath.\DIRECTORY_SEPARATOR.'services.{yml,yaml,xml,php}';

        foreach (\glob($globPattern, \GLOB_BRACE) as $serviceDefinitionPath) {
            try {
                $delegatingLoader->load($serviceDefinitionPath);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($serviceDefinitionPath, $throwable);
            }
        }
    }

    private function registerFlowComponentsFromBuilder(ContainerBuilder $containerBuilder, string $path): void
    {
        $this->flowComponentBuilder->reset();

        foreach (\glob($path.\DIRECTORY_SEPARATOR.'*.php') as $flowComponentScript) {
            // prevent access to object context
            (static function (string $file) {
                include $file;
            })($flowComponentScript);
        }

        $flowComponents = [
            ...$this->flowComponentBuilder->buildExplorers(),
            ...$this->flowComponentBuilder->buildEmitters(),
            ...$this->flowComponentBuilder->buildReceivers(),
            ...$this->flowComponentBuilder->buildStatusReporters(),
        ];

        foreach ($flowComponents as $flowComponent) {
            $this->setSyntheticServices($containerBuilder, [
                \bin2hex(\random_bytes(16)) => $flowComponent,
            ]);
        }
    }

    private function removeAboutToBeSyntheticlyInjectedServices(ContainerBuilder $containerBuilder): void
    {
        $automaticLoadedDefinitionsToRemove = [];

        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? (string) $id;

            if (!\class_exists($class)) {
                continue;
            }

            if (\is_a($class, PortalContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = (string) $id;
            }

            if (\is_a($class, PortalExtensionContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = (string) $id;
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
            $definitionId = (string) $id;
            $containerBuilder->set($definitionId, $service);
            $definition = (new Definition())
                ->setSynthetic(true)
                ->setClass(\get_class($service));
            $containerBuilder->setDefinition($definitionId, $definition);
        }
    }
}
