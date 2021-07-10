<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Portal;

use Composer\Autoload\ClassLoader;
use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Core\Storage\Filesystem\FilesystemFactory;
use Heptacom\HeptaConnect\Portal\Base\Builder\FlowComponent;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use HeptacomFixture\Portal\A\Portal;
use HeptacomFixture\Portal\Extension\PortalExtension;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiable
 * @covers \Heptacom\HeptaConnect\Core\Portal\Exception\ServiceNotInstantiableEndlessLoopDetected
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalConfiguration
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalLogger
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder
 * @covers \Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass\AddPortalConfigurationBindingsCompilerPass
 */
class PortalStackServiceContainerBuilderTest extends TestCase
{
    private ClassLoader $classLoader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classLoader = new ClassLoader();
        $this->classLoader->addPsr4('HeptacomFixture\\Portal\\A\\', __DIR__.'/../../test-composer-integration/portal-package/src/');
        $this->classLoader->addPsr4('HeptacomFixture\\Portal\\Extension\\', __DIR__.'/../../test-composer-integration/portal-package-extension/src/');
        $this->classLoader->register();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->classLoader->unregister();
    }

    public function testServiceRetrieval(): void
    {
        $configurationService = $this->createMock(ConfigurationServiceInterface::class);
        $configurationService->expects(self::atLeastOnce())
            ->method('getPortalNodeConfiguration')
            ->willReturn([]);

        $builder = new PortalStackServiceContainerBuilder(
            $this->createMock(LoggerInterface::class),
            $this->createMock(NormalizationRegistryContract::class),
            $this->createMock(PortalStorageFactory::class),
            $this->createMock(ResourceLockingContract::class),
            $this->createMock(ProfilerFactoryContract::class),
            $this->createMock(StorageKeyGeneratorContract::class),
            $this->createMock(FlowComponent::class),
            $this->createMock(FilesystemFactory::class),
            $configurationService,
        );
        $container = $builder->build(
            new Portal(),
            new PortalExtensionCollection([
                new PortalExtension(),
            ]),
            $this->createMock(PortalNodeKeyInterface::class),
        );

        static::assertTrue($container->has(ClientInterface::class));
        static::assertTrue($container->has(ConfigurationContract::class));
        static::assertTrue($container->has(DeepCloneContract::class));
        static::assertTrue($container->has(DeepObjectIteratorContract::class));
        static::assertTrue($container->has(FilesystemInterface::class));
        static::assertTrue($container->has(LoggerInterface::class));
        static::assertTrue($container->has(NormalizationRegistryContract::class));
        static::assertTrue($container->has(PortalContract::class));
        static::assertTrue($container->has(PortalExtensionCollection::class));
        static::assertTrue($container->has(PortalNodeKeyInterface::class));
        static::assertTrue($container->has(PortalStorageInterface::class));
        static::assertTrue($container->has(ProfilerContract::class));
        static::assertTrue($container->has(RequestFactoryInterface::class));
        static::assertTrue($container->has(ResourceLockFacade::class));
        static::assertTrue($container->has(UriFactoryInterface::class));
    }
}
