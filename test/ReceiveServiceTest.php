<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Core\Receive\ReceiveService;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowReceiver;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\PortalNodeExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\TypedMappedDatasetEntityCollection;
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
        $receiveContext = $this->createMock(ReceiveContextInterface::class);
        $mappingService = $this->createMock(MappingServiceInterface::class);

        $logger = $this->createMock(LoggerInterface::class);

        $portalNodeRegistry = $this->createMock(PortalNodeRegistryInterface::class);

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::exactly($count))
            ->method('getDatasetEntityClassName')
            ->willReturn(FooBarEntity::class);

        $mappedDatasetEntity = $this->createMock(MappedDatasetEntityStruct::class);
        $mappedDatasetEntity->expects(static::exactly($count * 2))
            ->method('getMapping')
            ->willReturn($mapping);

        $emitService = new ReceiveService($mappingService, $receiveContext, $logger, $portalNodeRegistry);
        $emitService->receive(new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity)));
    }

    /**
     * @dataProvider provideReceiveCount
     */
    public function testMissingReceiver(int $count): void
    {
        $emitContext = $this->createMock(ReceiveContextInterface::class);
        $mappingService = $this->createMock(MappingServiceInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE());

        $portalNode = $this->createMock(PortalNodeInterface::class);
        $portalNode->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getReceivers')
            ->willReturn(new ReceiverCollection());

        $portalNodeRegistry = $this->createMock(PortalNodeRegistryInterface::class);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNode')
            ->willReturn($portalNode);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNodeExtensions')
            ->willReturn(new PortalNodeExtensionCollection());

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

        $emitService = new ReceiveService($mappingService, $emitContext, $logger, $portalNodeRegistry);
        $emitService->receive(new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity)));
    }

    /**
     * @dataProvider provideReceiveCount
     */
    public function testReceiverFailing(int $count): void
    {
        $receiveContext = $this->createMock(ReceiveContextInterface::class);
        $mappingService = $this->createMock(MappingServiceInterface::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::RECEIVE_NO_THROW());

        $portalNode = $this->createMock(PortalNodeInterface::class);
        $portalNode->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getReceivers')
            ->willReturn(new ReceiverCollection([new ThrowReceiver()]));

        $portalNodeRegistry = $this->createMock(PortalNodeRegistryInterface::class);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNode')
            ->willReturn($portalNode);
        $portalNodeRegistry->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('getPortalNodeExtensions')
            ->willReturn(new PortalNodeExtensionCollection());

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

        $emitService = new ReceiveService($mappingService, $receiveContext, $logger, $portalNodeRegistry);
        $emitService->receive(new TypedMappedDatasetEntityCollection(FooBarEntity::class, \array_fill(0, $count, $mappedDatasetEntity)));
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
