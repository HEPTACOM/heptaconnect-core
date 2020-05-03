<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Core\Emit\EmitService;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEmitter;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Emit\EmitService
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 * @covers \Heptacom\HeptaConnect\Core\Emit\Component\EmitResult
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

        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitterRegistry = $this->createMock(EmitterRegistryInterface::class);
        $emitterRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([$emitter]);

        $logger = $this->createMock(LoggerInterface::class);

        $mapping = $this->createMock(MappingInterface::class);

        $emitService = new EmitService($emitContext, $mappingService, $emitterRegistry, $logger);
        $result = $emitService->emit(new MappingCollection(...\array_fill(0, $count, $mapping)));
        $this->assertEquals($count, $result->count());
    }

    /**
     * @dataProvider provideEmitCount
     */
    public function testMissingEmitter(int $count): void
    {
        $emitContext = $this->createMock(EmitContextInterface::class);

        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $emitterRegistry = $this->createMock(EmitterRegistryInterface::class);
        $emitterRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? $this->atLeastOnce() : $this->never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_EMITTER_FOR_TYPE());

        $mapping = $this->createMock(MappingInterface::class);

        $emitService = new EmitService($emitContext, $mappingService, $emitterRegistry, $logger);
        $result = $emitService->emit(new MappingCollection(...\array_fill(0, $count, $mapping)));
        $this->assertEquals(0, $result->count());
    }

    public function provideEmitCount(): array
    {
        return [
            [0],
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
        ];
    }
}
