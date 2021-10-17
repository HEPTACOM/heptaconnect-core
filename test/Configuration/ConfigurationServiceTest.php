<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\ConfigurationService;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @covers \Heptacom\HeptaConnect\Core\Configuration\ConfigurationService
 */
class ConfigurationServiceTest extends TestCase
{
    public function testConfigurationTemplateLoading(): void
    {
        $registry = $this->createMock(PortalRegistryInterface::class);
        $registry->method('getPortal')->willReturn(new class() extends PortalContract {
            public function getConfigurationTemplate(): OptionsResolver
            {
                return parent::getConfigurationTemplate()
                    ->setDefined(['limit'])
                    ->setDefault('limit', 100);
            }
        });
        $registry->method('getPortalExtensions')->willReturn(new PortalExtensionCollection());
        $storage = $this->createMock(ConfigurationStorageContract::class);
        $storage->method('getConfiguration')->willReturn([]);
        $configService = new ConfigurationService($registry, $storage, new NullAdapter(), $this->createMock(StorageKeyGeneratorContract::class));

        $config = $configService->getPortalNodeConfiguration($this->createMock(PortalNodeKeyInterface::class));
        static::assertArrayHasKey('limit', $config);
        static::assertEquals(100, $config['limit']);
    }

    public function testConfigurationTemplateLoadingWithExtensionOverrides(): void
    {
        $registry = $this->createMock(PortalRegistryInterface::class);
        $registry->method('getPortal')->willReturn(new class() extends PortalContract {
            public function getConfigurationTemplate(): OptionsResolver
            {
                return parent::getConfigurationTemplate()
                    ->setDefined(['limit'])
                    ->setDefault('limit', 100);
            }
        });
        $registry->method('getPortalExtensions')->willReturn(new PortalExtensionCollection([
            new class() extends PortalExtensionContract {
                public function supports(): string
                {
                    return PortalContract::class;
                }

                public function extendConfiguration(OptionsResolver $template): OptionsResolver
                {
                    return parent::extendConfiguration($template)
                        ->setDefined(['offset'])
                        ->setDefault('offset', 1000)
                        ->setDefault('limit', 200);
                }
            },
        ]));
        $storage = $this->createMock(ConfigurationStorageContract::class);
        $storage->method('getConfiguration')->willReturn([]);
        $configService = new ConfigurationService($registry, $storage, new NullAdapter(), $this->createMock(StorageKeyGeneratorContract::class));

        $config = $configService->getPortalNodeConfiguration($this->createMock(PortalNodeKeyInterface::class));
        static::assertArrayHasKey('limit', $config);
        static::assertEquals(200, $config['limit']);
        static::assertArrayHasKey('offset', $config);
        static::assertEquals(1000, $config['offset']);
    }
}
