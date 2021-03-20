<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Emission\EmitService;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowEmitter;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
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
        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $stack = new EmitterStack([]);
        $stackBuilder = $this->createMock(EmitterStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(true);
        $stackBuilderFactory = $this->createMock(EmitterStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createEmitterStackBuilder')->willReturn($stackBuilder);

        $emitService = new EmitService(
            $this->createMock(EmitContextInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(StorageKeyGeneratorContract::class),
            $stackBuilderFactory,
        );
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

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $stack = new EmitterStack([]);
        $stackBuilder = $this->createMock(EmitterStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(true);
        $stackBuilderFactory = $this->createMock(EmitterStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createEmitterStackBuilder')->willReturn($stackBuilder);

        $emitService = new EmitService(
            $emitContext,
            $logger,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(StorageKeyGeneratorContract::class),
            $stackBuilderFactory,
    );
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

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $stack = new EmitterStack([new ThrowEmitter()]);
        $stackBuilder = $this->createMock(EmitterStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(false);
        $stackBuilderFactory = $this->createMock(EmitterStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createEmitterStackBuilder')->willReturn($stackBuilder);

        $emitService = new EmitService(
            $emitContext,
            $logger,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(StorageKeyGeneratorContract::class),
            $stackBuilderFactory,
        );
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
