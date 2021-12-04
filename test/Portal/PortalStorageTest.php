<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Portal;

use Heptacom\HeptaConnect\Core\Portal\PortalStorage;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Portal\PortalStorage
 */
class PortalStorageTest extends TestCase
{
    public function testLoggingNormalizationIssues(): void
    {
        $portalNodeKey = $this->createMock(PortalNodeKeyInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $portalStorage = $this->createMock(PortalStorageContract::class);
        $normalizationRegistry = $this->createMock(NormalizationRegistryContract::class);

        $messages = [];
        $captureCb = static function ($m) use (&$messages) {
            return $messages[] = $m;
        };

        $logger->method('emergency')->willReturnCallback($captureCb);
        $logger->method('alert')->willReturnCallback($captureCb);
        $logger->method('critical')->willReturnCallback($captureCb);
        $logger->method('error')->willReturnCallback($captureCb);
        $logger->method('warning')->willReturnCallback($captureCb);

        $portalStorage->method('has')->willReturn(true);
        $portalStorage->method('getMultiple')->willReturn(['foobar' => null], []);

        $storage = new PortalStorage($normalizationRegistry, $portalStorage, $logger, $portalNodeKey);

        $storage->set('foobar', 'value');
        static::assertNotEmpty($messages);

        $messages = [];
        $storage->get('foobar', 'fallback');
        static::assertNotEmpty($messages);

        $messages = [];
        $storage->setMultiple([
            'foobar' => 'value',
        ]);
        static::assertNotEmpty($messages);

        $messages = [];
        $storage->getMultiple(['foobar']);
        static::assertNotEmpty($messages);

        $messages = [];
        $storage->setMultiple([]);
        static::assertEmpty($messages);

        $messages = [];
        $storage->getMultiple([]);
        static::assertEmpty($messages);
    }
}
