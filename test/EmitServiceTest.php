<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Core\Emit\EmitService;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEmitter;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowEmitter;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Emit\EmitService
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 */
class EmitServiceTest extends TestCase
{
    /**
     * @dataProvider provideEmitCount
     */
    public function testEmitCount(int $count): void
    {
        $emitter = new FooBarEmitter($count);

        $emitContext = $this->createMock(EmitContextInterface::class);

        $emitterRegistry = $this->createMock(EmitterRegistryInterface::class);
        $emitterRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([$emitter]);

        $logger = $this->createMock(LoggerInterface::class);

        $messageBus = $this->createMock(MessageBusInterface::class);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $emitterRegistry, $logger, $messageBus);
        $emitService->emit(new MappingCollection(...\array_fill(0, $count, $mapping)));
    }

    /**
     * @dataProvider provideEmitCount
     */
    public function testMissingEmitter(int $count): void
    {
        $emitContext = $this->createMock(EmitContextInterface::class);

        $emitterRegistry = $this->createMock(EmitterRegistryInterface::class);
        $emitterRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? $this->atLeastOnce() : $this->never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_EMITTER_FOR_TYPE());

        $messageBus = $this->createMock(MessageBusInterface::class);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $emitterRegistry, $logger, $messageBus);
        $emitService->emit(new MappingCollection(...\array_fill(0, $count, $mapping)));
    }

    /**
     * @dataProvider provideEmitCount
     */
    public function testEmitterFailing(int $count): void
    {
        $emitter = new ThrowEmitter();

        $emitContext = $this->createMock(EmitContextInterface::class);

        $emitterRegistry = $this->createMock(EmitterRegistryInterface::class);
        $emitterRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([$emitter]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? $this->atLeastOnce() : $this->never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_THROW());

        $messageBus = $this->createMock(MessageBusInterface::class);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitService = new EmitService($emitContext, $emitterRegistry, $logger, $messageBus);
        $emitService->emit(new MappingCollection(...\array_fill(0, $count, $mapping)));
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
