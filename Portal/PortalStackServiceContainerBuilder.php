<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\File\FileReferenceFactory;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\File\Filesystem\Contract\FilesystemFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddConfigurationBindingsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddHttpMiddlewareClientCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddHttpMiddlewareCollectorCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AllDefinitionDefaultsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\BuildDefinitionForFlowComponentRegistryCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\RemoveAutoPrototypedDefinitionsCompilerPass;
use Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\SetConfigurationAsParameterCompilerPass;
use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Core\Storage\Filesystem\FilesystemFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerUrlProviderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface;
use Heptacom\HeptaConnect\Core\Web\Http\HttpClient;
use Heptacom\HeptaConnect\Core\Web\Http\HttpKernel;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceResolverContract;
use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Contract\FilesystemInterface as HeptaConnectFilesystemInterface;
use Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PackageContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Portal\Base\Portal\PackageCollection;
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
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpKernelInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageCurlShellFormatterContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageFormatterContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageMultiPartFormDataBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\Psr7MessageRawHttpFormatterContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerUrlProviderInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.LongVariableName)
 */
final class PortalStackServiceContainerBuilder implements PortalStackServiceContainerBuilderInterface
{
    public const STATUS_REPORTER_SOURCE_TAG = 'heptaconnect.flow_component.status_reporter_source';

    public const EMITTER_SOURCE_TAG = 'heptaconnect.flow_component.emitter_source';

    public const EXPLORER_SOURCE_TAG = 'heptaconnect.flow_component.explorer_source';

    public const RECEIVER_SOURCE_TAG = 'heptaconnect.flow_component.receiver_source';

    public const WEB_HTTP_HANDLER_SOURCE_TAG = 'heptaconnect.flow_component.web_http_handler_source';

    public const SERVICE_FROM_A_PORTAL_TAG = 'heptaconnect.service_from_a_portal';

    public const PORTAL_CONFIGURATION_PARAMETER_PREFIX = 'portal_config.';

    private ?DirectEmissionFlowContract $directEmissionFlow = null;

    private ?FileReferenceResolverContract $fileReferenceResolver = null;

    private ?HttpHandleServiceInterface $httpHandleService = null;

    /**
     * @var array<class-string<PackageContract>, PackageContract>
     */
    private array $alreadyBuiltPackages = [];

    public function __construct(
        private LoggerInterface $logger,
        private NormalizationRegistryContract $normalizationRegistry,
        private PortalStorageFactory $portalStorageFactory,
        private ResourceLockingContract $resourceLocking,
        private ProfilerFactoryContract $profilerFactory,
        private StorageKeyGeneratorContract $storageKeyGenerator,
        private FilesystemFactory $filesystemFactory,
        private ConfigurationServiceInterface $configurationService,
        private PublisherInterface $publisher,
        private HttpHandlerUrlProviderFactoryInterface $httpHandlerUrlProviderFactory,
        private RequestStorageContract $requestStorage,
        private FilesystemFactoryInterface $filesystemFactory2,
        private Psr7MessageCurlShellFormatterContract $psr7MessageCurlShellFormatter,
        private Psr7MessageRawHttpFormatterContract $psr7MessageRawHttpFormatter,
        private Psr7MessageMultiPartFormDataBuilderInterface $psr7MessageMultiPartFormDataBuilder,
    ) {
    }

    /**
     * @throws DelegatingLoaderLoadException
     */
    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeKeyInterface $portalNodeKey
    ): ContainerBuilder {
        $portalNodeKey = $portalNodeKey->withAlias();
        $containerBuilder = new ContainerBuilder();

        $seenDefinitions = [];
        $flowBuilderFiles = [];

        $this->alreadyBuiltPackages = [];

        /** @var PackageContract $package */
        foreach ([$portal, ...$portalExtensions] as $package) {
            $containerConfigurationPath = $package->getContainerConfigurationPath();
            $flowComponentsPath = $package->getFlowComponentsPath();

            $prototypedIds = $this->getChangedServiceIds($containerBuilder, function (
                ContainerBuilder $containerBuilder
            ) use (
                $flowComponentsPath,
                $containerConfigurationPath,
                $package
            ): void {
                $this->registerPsr4Prototype($containerBuilder, $package->getPsr4(), [
                    $containerConfigurationPath,
                    $flowComponentsPath,
                ]);
            });

            $definedIds = $this->getChangedServiceIds($containerBuilder, function (
                ContainerBuilder $containerBuilder
            ) use (
                $package
            ): void {
                $this->buildPackage($package, $containerBuilder);
            });

            $containerBuilder->addCompilerPass(new RemoveAutoPrototypedDefinitionsCompilerPass(
                \array_diff($prototypedIds, $definedIds),
                $package->getContainerExcludedClasses()
            ), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

            /** @var Definition[] $newDefinitions */
            $newDefinitions = \array_diff_key($containerBuilder->getDefinitions(), $seenDefinitions);
            $seenDefinitions = $containerBuilder->getDefinitions();
            $packageClass = $package::class;

            $this->tagDefinitionSource($newDefinitions, ExplorerContract::class, self::EXPLORER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, EmitterContract::class, self::EMITTER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, ReceiverContract::class, self::RECEIVER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, StatusReporterContract::class, self::STATUS_REPORTER_SOURCE_TAG, $packageClass);
            $this->tagDefinitionSource($newDefinitions, HttpHandlerContract::class, self::WEB_HTTP_HANDLER_SOURCE_TAG, $packageClass);

            $globMatches = \glob($flowComponentsPath . \DIRECTORY_SEPARATOR . '*.php');
            $flowBuilderFiles[$packageClass] = $globMatches !== false ? $globMatches : [];
        }

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->addTag(self::SERVICE_FROM_A_PORTAL_TAG);
        }

        $fileReferenceFactory = new FileReferenceFactory(
            $portalNodeKey,
            Psr17FactoryDiscovery::findStreamFactory(),
            $this->normalizationRegistry,
            $this->requestStorage
        );

        $this->setSyntheticServices($containerBuilder, [
            PortalContract::class => $portal,
            PortalExtensionCollection::class => $portalExtensions,
            PackageCollection::class => new PackageCollection($this->alreadyBuiltPackages),
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
            PublisherInterface::class => $this->publisher,
            HttpHandlerUrlProviderInterface::class => $this->httpHandlerUrlProviderFactory->factory($portalNodeKey),
            FileReferenceFactoryContract::class => $fileReferenceFactory,
            HeptaConnectFilesystemInterface::class => $this->filesystemFactory2->create($portalNodeKey),
            Psr7MessageCurlShellFormatterContract::class => $this->psr7MessageCurlShellFormatter,
            Psr7MessageRawHttpFormatterContract::class => $this->psr7MessageRawHttpFormatter,
            Psr7MessageMultiPartFormDataBuilderInterface::class => $this->psr7MessageMultiPartFormDataBuilder,
            HttpKernelInterface::class => new HttpKernel(
                $portalNodeKey,
                $this->httpHandleService,
                Psr17FactoryDiscovery::findStreamFactory(),
                Psr17FactoryDiscovery::findUploadedFileFactory()
            ),
        ]);
        $containerBuilder->setAlias($portal::class, PortalContract::class);
        $containerBuilder->setAlias(Psr7MessageFormatterContract::class, Psr7MessageRawHttpFormatterContract::class);

        if ($this->directEmissionFlow instanceof DirectEmissionFlowContract) {
            $this->setSyntheticServices($containerBuilder, [
                DirectEmissionFlowContract::class => $this->directEmissionFlow,
            ]);
        }

        if ($this->fileReferenceResolver instanceof FileReferenceResolverContract) {
            $this->setSyntheticServices($containerBuilder, [
                FileReferenceResolverContract::class => $this->fileReferenceResolver,
            ]);
        }

        $containerBuilder->setDefinition(DeepCloneContract::class, new Definition());
        $containerBuilder->setDefinition(DeepObjectIteratorContract::class, new Definition());
        $containerBuilder->setDefinition(ClientInterface::class, (new Definition())->setFactory([Psr18ClientDiscovery::class, 'find']));
        $containerBuilder->setDefinition(RequestFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findRequestFactory']));
        $containerBuilder->setDefinition(ServerRequestFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findServerRequestFactory']));
        $containerBuilder->setDefinition(UriFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findUriFactory']));
        $containerBuilder->setDefinition(ResponseFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findResponseFactory']));
        $containerBuilder->setDefinition(StreamFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findStreamFactory']));
        $containerBuilder->setDefinition(UploadedFileFactoryInterface::class, (new Definition())->setFactory([Psr17FactoryDiscovery::class, 'findUploadedFileFactory']));
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
        $containerBuilder->setDefinition(
            ConfigurationContract::class,
            (new Definition())
                ->setClass(PortalConfiguration::class)
                ->setArguments([
                    new BoundArgument('%' . self::PORTAL_CONFIGURATION_PARAMETER_PREFIX . '%'),
                ])
        );

        $containerBuilder->addCompilerPass(new BuildDefinitionForFlowComponentRegistryCompilerPass($flowBuilderFiles));
        $containerBuilder->addCompilerPass(new AddHttpMiddlewareClientCompilerPass());
        $containerBuilder->addCompilerPass(new AddHttpMiddlewareCollectorCompilerPass());
        $containerBuilder->addCompilerPass(new AllDefinitionDefaultsCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);

        try {
            $containerBuilder->addCompilerPass(
                new SetConfigurationAsParameterCompilerPass(
                    $this->configurationService->getPortalNodeConfiguration($portalNodeKey) ?? []
                ),
                PassConfig::TYPE_BEFORE_OPTIMIZATION,
                20000
            );
        } catch (\Throwable $throwable) {
            $this->logger->error(LogMessage::PORTAL_NODE_CONFIGURATION_INVALID(), [
                'portal_node_key' => $portalNodeKey,
                'exception' => $throwable,
            ]);
        }

        $containerBuilder->addCompilerPass(new AddConfigurationBindingsCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10000);
        $this->alreadyBuiltPackages = [];

        return $containerBuilder;
    }

    public function setDirectEmissionFlow(DirectEmissionFlowContract $directEmissionFlow): void
    {
        $this->directEmissionFlow = $directEmissionFlow;
    }

    public function setFileReferenceResolver(FileReferenceResolverContract $fileReferenceResolver): void
    {
        $this->fileReferenceResolver = $fileReferenceResolver;
    }

    public function setHttpHandleService(HttpHandleServiceInterface $httpHandleService): void
    {
        $this->httpHandleService = $httpHandleService;
    }

    /**
     * @param callable(ContainerBuilder $containerBuilder):void $registration
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

        $registration($containerBuilder);

        $allPreviousServices = \array_keys($containerBuilder->findTaggedServiceIds($tag));

        foreach ($containerBuilder->getDefinitions() as $definition) {
            $definition->clearTag($tag);
        }

        return \array_merge(
            \array_diff($containerBuilder->getServiceIds(), $currentIds),
            \array_diff($containerBuilder->getServiceIds(), $allPreviousServices),
        );
    }

    private function buildPackage(
        PackageContract $package,
        ContainerBuilder $containerBuilder
    ): void {
        $packageType = \get_class($package);

        if (isset($this->alreadyBuiltPackages[$packageType])) {
            return;
        }

        $this->alreadyBuiltPackages[$packageType] = $package;

        foreach ($package->getAdditionalPackages() as $additionalPackage) {
            $this->buildPackage($additionalPackage, $containerBuilder);
        }

        $package->buildContainer($containerBuilder);
    }

    /**
     * @param Definition[] $definitions
     *
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

            $definedTags = $definition->getTag($tag);
            $definition->clearTag($tag);

            if ($definedTags === []) {
                $definedTags[] = ['source' => $packageClass];
            }

            foreach ($definedTags as $definedTag) {
                $definedTag['source'] = $packageClass;
                $definition->addTag($tag, $definedTag);
            }
        }
    }

    /**
     * @param array<string, string> $psr4
     * @param string[]              $exclude
     */
    private function registerPsr4Prototype(
        ContainerBuilder $containerBuilder,
        array $psr4,
        array $exclude = []
    ): void {
        $exclude = \array_filter($exclude, 'is_dir');

        foreach ($psr4 as $namespace => $path) {
            $fileLocator = new FileLocator($path);
            $fileLoader = new GlobFileLoader($containerBuilder, $fileLocator);

            $excludesPerNamespace = \array_filter(
                $exclude,
                static fn (string $excludeItem): bool => \str_starts_with($excludeItem, $path)
            );

            $fileLoader->registerClasses(
                new Definition(),
                $namespace,
                \rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . '*',
                $excludesPerNamespace
            );
        }
    }

    /**
     * @param array<class-string, object> $services
     */
    private function setSyntheticServices(ContainerBuilder $containerBuilder, array $services): void
    {
        foreach ($services as $id => $service) {
            $containerBuilder->set($id, $service);
            $definition = (new Definition())
                ->setSynthetic(true)
                ->setClass($service::class);
            $containerBuilder->setDefinition($id, $definition);
        }
    }
}
