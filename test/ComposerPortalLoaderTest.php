<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationLoader;
use Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader;
use Heptacom\HeptaConnect\Core\Portal\PortalFactory;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use HeptacomFixture\Portal\A\Portal;
use HeptacomFixture\Portal\Extension\PortalExtension;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Component\Composer\PackageConfiguration
 * @covers \Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationClassMap
 * @covers \Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationCollection
 * @covers \Heptacom\HeptaConnect\Core\Component\Composer\PackageConfigurationLoader
 * @covers \Heptacom\HeptaConnect\Core\Portal\ComposerPortalLoader
 * @covers \Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalFactory
 */
class ComposerPortalLoaderTest extends TestCase
{
    public function testInstantiateFromComposer(): void
    {
        require_once __DIR__.'/../test-composer-integration/portal-package/src/Portal.php';
        require_once __DIR__.'/../test-composer-integration/portal-package-extension/src/PortalExtension.php';

        $poolItem = $this->createMock(CacheItemInterface::class);
        $poolItem->method('isHit')->willReturn(false);
        $cachePool = $this->createMock(CacheItemPoolInterface::class);
        $cachePool->method('getItem')->willReturn($poolItem);

        $loader = new ComposerPortalLoader(
            new PackageConfigurationLoader(__DIR__.'/../test-composer-integration/composer.json', $cachePool),
            new PortalFactory(),
            $this->createMock(LoggerInterface::class)
        );
        $portals = [...$loader->getPortals()];
        $portalExtensions = $loader->getPortalExtensions();

        static::assertCount(1, $portals);
        static::assertCount(1, $portalExtensions);

        /** @var PortalContract $portal */
        foreach ($portals as $portal) {
            static::assertInstanceOf(Portal::class, $portal);
        }

        foreach ($portalExtensions as $portalExtension) {
            static::assertInstanceOf(PortalExtension::class, $portalExtension);
        }
    }
}
