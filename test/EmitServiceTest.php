<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\EmitService;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowEmitter;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Emission\EmitService
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 */
class EmitServiceTest extends TestCase
{
    /**
     * @dataProvider provideEmitCount
     */
    public function testEmitCount(int $count): void
    {
        $emitContext = $this->createMock(EmitContextInterface::class);

        $logger = $this->createMock(LoggerInterface::class);

        $messageBus = $this->createMock(MessageBusInterface::class);

        $portalNodeRegistry = $this->createMock(PortalRegistryInterface::class);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $logger, $messageBus, $portalNodeRegistry);
        $emitService->emit(new TypedMappingCollection(FooBarEntity::class, \array_fill(0, $count, $mapping)));
    }

    /**
     * @dataProvider provideEmitCount
     */
    public function testMissingEmitter(int $count): void
    {
        $emitContext = $this->createMock(EmitContextInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_EMITTER_FOR_TYPE());

        $messageBus = $this->createMock(MessageBusInterface::class);

        $portalNode = $this->createMock(PortalInterface::class);
        $portalNode->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getEmitters')
            ->willReturn(new EmitterCollection());

        $portalNodeRegistry = $this->createMock(PortalRegistryInterface::class);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNode')
            ->willReturn($portalNode);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNodeExtensions')
            ->willReturn(new PortalExtensionCollection());

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $logger, $messageBus, $portalNodeRegistry);
        $emitService->emit(new TypedMappingCollection(FooBarEntity::class, \array_fill(0, $count, $mapping)));
    }

    /**
     * @dataProvider provideEmitCount
     */
    public function testEmitterFailing(int $count): void
    {
        $emitContext = $this->createMock(EmitContextInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_THROW());

        $messageBus = $this->createMock(MessageBusInterface::class);

        $portalNode = $this->createMock(PortalInterface::class);
        $portalNode->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getEmitters')
            ->willReturn(new EmitterCollection([new ThrowEmitter()]));

        $portalNodeRegistry = $this->createMock(PortalRegistryInterface::class);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNode')
            ->willReturn($portalNode);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNodeExtensions')
            ->willReturn(new PortalExtensionCollection());

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $logger, $messageBus, $portalNodeRegistry);
        $emitService->emit(new TypedMappingCollection(FooBarEntity::class, \array_fill(0, $count, $mapping)));
    }

    /**
     * @return iterable<array-key, array<array-key, int>>
     */
    public function provideEmitCount(): iterable
    {
        yield [0];
        yield [1];
        yield [2];
        yield [3];
        yield [4];
        yield [5];
        yield [6];
        yield [7];
        yield [8];
        yield [9];
        yield [10];
    }
}
