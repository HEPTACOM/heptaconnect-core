<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface;
use Heptacom\HeptaConnect\Core\Reception\ReceiveService;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Reception\ReceiveService
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 */
class ReceiveServiceTest extends TestCase
{
    /**
     * @dataProvider provideReceiveCount
     */
    public function testReceiveCount(int $count): void
    {
        $receiveContext = $this->createMock(ReceiveContextInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getMapping')
            ->willReturn($mapping);

        $storageKeyGenerator = $this->createMock(StorageKeyGeneratorContract::class);

        $stack = new ReceiverStack([]);
        $stackBuilder = $this->createMock(ReceiverStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(true);
        $stackBuilderFactory = $this->createMock(ReceiverStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createReceiverStackBuilder')->willReturn($stackBuilder);

        $receiveService = new ReceiveService(
            $receiveContext,
            $logger,
            $storageKeyGenerator,
            $stackBuilderFactory,
            $this->createMock(ReceptionActorInterface::class),
        );
        $receiveService->receive(
            new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity))
        );
    }

    /**
     * @dataProvider provideReceiveCount
     */
    public function testMissingReceiver(int $count): void
    {
        $receiveContext = $this->createMock(ReceiveContextInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE());

        $stack = new ReceiverStack([]);
        $stackBuilder = $this->createMock(ReceiverStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(true);
        $stackBuilderFactory = $this->createMock(ReceiverStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createReceiverStackBuilder')->willReturn($stackBuilder);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);
        $mapping->expects(static::atLeast($count))
            ->method('getPortalNodeKey')
            ->willReturn($this->createMock(PortalNodeKeyInterface::class));

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects(static::atLeast($count))
            ->method('getMapping')
            ->willReturn($mapping);

        $storageKeyGenerator = $this->createMock(StorageKeyGeneratorContract::class);

        $receiveService = new ReceiveService(
            $receiveContext,
            $logger,
            $storageKeyGenerator,
            $stackBuilderFactory,
            $this->createMock(ReceptionActorInterface::class),
        );
        $receiveService->receive(
            new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity))
        );
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
