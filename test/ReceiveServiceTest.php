<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Receive\Contract\ReceiverRegistryInterface;
use Heptacom\HeptaConnect\Core\Receive\ReceiveService;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarReceiver;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowReceiver;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Receive\ReceiveService
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 */
class ReceiveServiceTest extends TestCase
{
    /**
     * @dataProvider provideReceiveCount
     */
    public function testReceiveCount(int $count): void
    {
        $emitter = new FooBarReceiver();

        $receiveContext = $this->createMock(ReceiveContextInterface::class);

        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $receiverRegistry = $this->createMock(ReceiverRegistryInterface::class);
        $receiverRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([$emitter]);

        $logger = $this->createMock(LoggerInterface::class);

        $mapping = $this->createMock(MappingInterface::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects($this->exactly($count * 2))
            ->method('getMapping')
            ->willReturn($mapping);

        $emitService = new ReceiveService($receiverRegistry, $mappingService, $receiveContext, $logger);
        $emitService->receive(new MappedDatasetEntityCollection(...\array_fill(0, $count, $mappedDatasetEntity)));
    }

    /**
     * @dataProvider provideReceiveCount
     */
    public function testMissingReceiver(int $count): void
    {
        $emitContext = $this->createMock(ReceiveContextInterface::class);

        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $receiverRegistry = $this->createMock(ReceiverRegistryInterface::class);
        $receiverRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? $this->atLeastOnce() : $this->never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE());

        $mapping = $this->createMock(MappingInterface::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects($this->exactly($count))
            ->method('getMapping')
            ->willReturn($mapping);

        $emitService = new ReceiveService($receiverRegistry, $mappingService, $emitContext, $logger);
        $emitService->receive(new MappedDatasetEntityCollection(...\array_fill(0, $count, $mappedDatasetEntity)));
    }

    /**
     * @dataProvider provideReceiveCount
     */
    public function testReceiverFailing(int $count): void
    {
        $emitter = new ThrowReceiver();

        $receiveContext = $this->createMock(ReceiveContextInterface::class);

        $mappingService = $this->createMock(MappingServiceInterface::class);
        $mappingService->expects($this->exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $receiverRegistry = $this->createMock(ReceiverRegistryInterface::class);
        $receiverRegistry->expects($count > 0 ? $this->once() : $this->never())
            ->method('bySupport')
            ->with(FooBarEntity::class)
            ->willReturn([$emitter]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? $this->atLeastOnce() : $this->never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_THROW());

        $mapping = $this->createMock(MappingInterface::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects($this->exactly($count))
            ->method('getMapping')
            ->willReturn($mapping);

        $emitService = new ReceiveService($receiverRegistry, $mappingService, $receiveContext, $logger);
        $emitService->receive(new MappedDatasetEntityCollection(...\array_fill(0, $count, $mappedDatasetEntity)));
    }

    /**
     * @return iterable<array-key, array<array-key, int>>
     */
    public function provideReceiveCount(): iterable
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
