<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Reception\ReceptionActor;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowReceiver;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 * @covers \Heptacom\HeptaConnect\Core\Reception\ReceptionActor
 * @covers \Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable
 */
class ReceptionActorTest extends TestCase
{
    /**
     * @dataProvider provideEmitCount
     */
    public function testActingFails(int $count): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_THROW());

        $stack = new ReceiverStack([new ThrowReceiver()]);
        $stackBuilder = $this->createMock(ReceiverStackBuilderInterface::class);
        $stackBuilder->method('build')->willReturn($stack);
        $stackBuilder->method('pushSource')->willReturnSelf();
        $stackBuilder->method('pushDecorators')->willReturnSelf();
        $stackBuilder->method('isEmpty')->willReturn(false);
        $stackBuilderFactory = $this->createMock(ReceiverStackBuilderFactoryInterface::class);
        $stackBuilderFactory->method('createReceiverStackBuilder')->willReturn($stackBuilder);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects(static::atLeast($count))
            ->method('getMapping')
            ->willReturn($mapping);

        $receptionActor = new ReceptionActor(
            $logger,
            $this->createMock(MappingServiceInterface::class),
            new DeepObjectIteratorContract(),
        );
        $receptionActor->performReception(
            new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity)),
            $stack,
            $this->createMock(ReceiveContextInterface::class),
        );
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
