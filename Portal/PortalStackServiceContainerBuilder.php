<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddPortalConfigurationBindingsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AllDefinitionDefaultsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\BuildDefinitionForFlowComponentRegistryCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\RemoveAutoPrototypedDefinitionsCompilerPass;
use Heptacom\HeptaConnect\Core\Storage\Filesystem\FilesystemFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerUrlProviderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\HttpClient;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReporterContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerUrlProviderInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class PortalStackServiceContainerBuilder implements PortalStackServiceContainerBuilderInterface
{
    public const STATUS_REPORTER_SOURCE_TAG = 'heptaconnect.flow_component.status_reporter_source';

    public const EMITTER_SOURCE_TAG = 'heptaconnect.flow_component.emitter_source';

    public const EXPLORER_SOURCE_TAG = 'heptaconnect.flow_component.explorer_source';

    public const RECEIVER_SOURCE_TAG = 'heptaconnect.flow_component.receiver_source';

    public const WEB_HTTP_HANDLER_SOURCE_TAG = 'heptaconnect.flow_component.web_http_handler_source';

    public const SERVICE_FROM_A_PORTAL_TAG = 'heptaconnect.service_from_a_portal';

    private LoggerInterface $logger;

    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockingContract $resourceLocking;

    private ProfilerFactoryContract $profilerFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private FilesystemFactory $filesystemFactory;

    private ConfigurationServiceInterface $configurationService;

    private PublisherInterface $publisher;

    private ?DirectEmissionFlowContract $directEmissionFlow = null;

    private HttpHandlerUrlProviderFactoryInterface $httpHandlerUrlProviderFactory;

    public function __construct(
        LoggerInterface $logger,
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        ProfilerFactoryContract $profilerFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        FilesystemFactory $filesystemFactory,
        ConfigurationServiceInterface $configurationService,
        PublisherInterface $publisher,
        HttpHandlerUrlProviderFactoryInterface $httpHandlerUrlProviderFactory
    ) {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLocking = $resourceLocking;
        $this->profilerFactory = $profilerFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->filesystemFactory = $filesystemFactory;
        $this->configurationService = $configurationService;
        $this->publisher = $publisher;
        $this->httpHandlerUrlProviderFactory = $httpHandlerUrlProviderFactory;
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
        $prototypedIds = [];
        $definedIds = [];
        $flowBuilderFiles = [];

        /** @var PortalContract|PortalExtensionContract $package */
        foreach ([$portal, ...$portalExtensions] as $package) {
            $containerConfigurationPath = $package->getContainerConfigurationPath();
            $flowComponentsPath = $package->getFlowComponentsPath();

            $prototypedIds[] = $this->getChangedServiceIds($containerBuilder, function () use ($flowComponentsPath, $containerConfigurationPath, $package, $containerBuilder): void {
                $this->registerPsr4Prototype($containerBuilder, $package->getPsr4(), [
                    $containerConfigurationPath,
                    $flowComponentsPath,
                ]);
            });
            $definedIds[] = $this->getChangedServiceIds($containerBuilder, function () use ($containerConfigurationPath, $containerBuilder): void {
                $this->registerContainerConfiguration($containerBuilder, $containerConfigurationPath);
            });

            /** @var Definition[] $newDefinitions */
            $newDefinitions = \array_diff_key($containerBuilder->getDefinitions(), $seenDefinitions);
            $seenDefinitions = $containerBuilder->getDefinitions();
            $packageClass = \get_class($package);

            $this->tagDefinitionSource($newDefinitions, ExplorerContract::class, self::EXPLORER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, EmitterContract::class, self::EMITTER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, ReceiverContract::class, self::RECEIVER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, StatusReporterContract::class, self::STATUS_REPORTER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, HttpHandlerContract::class, self::WEB_HTTP_HANDLER_SOURCE_TAG, $packageClass);
            $flowBuilderFiles[$packageClass] = \glob($flowComponentsPath . \DIRECTORY_SEPARATOR . '*.php') ?: [];
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

        $portalConfiguration = new PortalConfiguration($configuration ?? []);

        $this->removeAboutToBeSyntheticlyInjectedServices($containerBuilder);
        $this->setSyntheticServices($containerBuilder, [
            PortalContract::class => $portal,
            PortalExtensionCollection::class => $portalExtensions,
            LoggerInterface::class => new PortalLogger(
                $this->logger,
                \sprintf('[%s] ', $this->storageKeyGenerator->serialize($portalNodeKey)),
                [
                    'portalNodeKey' => $portalNodeKey,
                ]
            ),
            NormalizationRegistryContract::class => $this->normalizationRegistry,
            PortalStorageInterface::class => $this->portalStorageFactory->createPortalStorage($portalNodeKey),
            ResourceLockFacade::class => new ResourceLockFacade($this->resourceLocking),
            PortalNodeKeyInterface::class => $portalNodeKey,
            ProfilerContract::class => $this->profilerFactory->factory('HeptaConnect\Portal::' . $this->storageKeyGenerator->serialize($portalNodeKey)),
            FilesystemInterface::class => $this->filesystemFactory->factory($portalNodeKey),
            ConfigurationContract::class => $portalConfiguration,
            PublisherInterface::class => $this->publisher,
            HttpHandlerUrlProviderInterface::class => $this->httpHandlerUrlProviderFactory->factory($portalNodeKey),
        ]);
        $containerBuilder->setAlias(\get_class($portal), PortalContract::class);

        if ($this->directEmissionFlow instanceof DirectEmissionFlowContract) {
            $this->setSyntheticServices($containerBuilder, [
                DirectEmissionFlowContract::class => $this->directEmissionFlow,
            ]);
        }

        $containerBuilder->setDefinition(DeepCloneContract::class, new Definition());
        $containerBuilder->setDefinition(DeepObjectIteratorContract::class, new Definition());
        $containerBuilder->setDefinition(ClientInterface::class, (new Definition())->setFactory([Psr18ClientDiscovery::class, 'find']));
        $containerBuilder->setDefinition(RequestFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findRequestFactory']));
        $containerBuilder->setDefinition(UriFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findUriFactory']));
        $containerBuilder->setDefinition(ResponseFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findResponseFactory']));
        $containerBuilder->setDefinition(StreamFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findStreamFactory']));
        $containerBuilder->setDefinition(
            HttpClientContract::class,
            (new Definition())
                ->setClass(HttpClient::class)
                ->setArguments([
                    new Reference(ClientInterface::class),
                    new Reference(UriFactoryInterface::class),
                ])
                ->addMethodCall('withExceptionTriggers', \range(400, 599), true)
                ->addMethodCall('withMaxRedirect', [20], true)
                ->addMethodCall('withMaxRetry', [2], true)
                ->addMethodCall('withMaxWaitTimeout', [], true)
        );

        $containerBuilder->addCompilerPass(new BuildDefinitionForFlowComponentRegistryCompilerPass($flowBuilderFiles));
        $containerBuilder->addCompilerPass(new RemoveAutoPrototypedDefinitionsCompilerPass(
            \array_diff(\array_merge([], ...$prototypedIds), \array_merge([], ...$definedIds))
        ), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);
        $containerBuilder->addCompilerPass(new AllDefinitionDefaultsCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);
        $containerBuilder->addCompilerPass(new AddPortalConfigurationBindingsCompilerPass($portalConfiguration), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

        return $containerBuilder;
    }

    public function setDirectEmissionFlow(DirectEmissionFlowContract $directEmissionFlow): void
    {
        $this->directEmissionFlow = $directEmissionFlow;
    }

    /**
     * @param callable():void $registration
     *
     * @return string[]
     *
     * @psalm-return array<string>
     */
    private function getChangedServiceIds(ContainerBuilder $containerBuilder, callable $registration): array
    {
        $currentIds = $containerBuilder->getServiceIds();
        $tag = '51f3a91f-900e-4828-a94b-5b3fb0ee7510';

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->addTag($tag);
        }

        $registration();

        $allPreviousServices = \array_keys($containerBuilder->findTaggedServiceIds($tag));

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->clearTag($tag);
        }

        return \array_merge(
            \array_diff($containerBuilder->getServiceIds(), $currentIds),
            \array_diff($containerBuilder->getServiceIds(), $allPreviousServices),
        );
    }

    /**
     * @param Definition[] $definitions
     * @psalm-param class-string $interface
     * @psalm-param class-string $packageClass
     */
    private function tagDefinitionSource(array $definitions, string $interface, string $tag, string $packageClass): void
    {
        foreach ($definitions as $id => $definition) {
            $class = $definition->getClass() ?? (string) $id;

            if (!\class_exists($class) || !\is_a($class, $interface, true)) {
                continue;
            }

            $definition->clearTag($tag);
            $definition->addTag($tag, ['source' => $packageClass]);
        }
    }

    private function registerPsr4Prototype(
        ContainerBuilder $containerBuilder,
        array $psr4,
        array $exclude = []
    ): void {
        $exclude = \array_filter($exclude, 'is_dir');

        foreach ($psr4 as $namespace => $path) {
            $fileLocator = new FileLocator($path);
            $fileLoader = new GlobFileLoader($containerBuilder, $fileLocator);

            $fileLoader->registerClasses(
                new Definition(),
                $namespace,
                \rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . '*',
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
        $directory = $containerConfigurationPath . \DIRECTORY_SEPARATOR . 'services.';
        $files = [
            $directory . 'yml',
            $directory . 'yaml',
            $directory . 'xml',
            $directory . 'php',
        ];

        foreach ($files as $serviceDefinitionPath) {
            if (!\is_file($serviceDefinitionPath)) {
                continue;
            }

            try {
                $delegatingLoader->load($serviceDefinitionPath);
            } catch (\Throwable $throwable) {
                throw new DelegatingLoaderLoadException($serviceDefinitionPath, $throwable);
            }
        }
    }

    private function removeAboutToBeSyntheticlyInjectedServices(ContainerBuilder $containerBuilder): void
    {
        $automaticLoadedDefinitionsToRemove = [];

        foreach ($containerBuilder->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? $id;

            if (!\class_exists($class)) {
                continue;
            }

            if (\is_a($class, PortalContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = $id;
            }

            if (\is_a($class, PortalExtensionContract::class, true)) {
                $automaticLoadedDefinitionsToRemove[] = $id;
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
