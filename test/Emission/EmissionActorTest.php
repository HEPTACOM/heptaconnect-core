<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\EmissionActor;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Core\Test\Fixture\ThrowEmitter;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\TypedMappingCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Component\LogMessage
 * @covers \Heptacom\HeptaConnect\Core\Emission\EmissionActor
 */
class EmissionActorTest extends TestCase
{
    /**
     * @dataProvider provideEmitCount
     */
    public function testActingFails(int $count): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($count > 0 ? static::atLeastOnce() : static::never())
            ->method('critical')
            ->with(LogMessage::EMIT_NO_THROW());

        $mapping = $this->createMock(MappingInterface::class);
        $mapping->expects(static::atLeast($count))
            ->method('getEntityType')
            ->willReturn(FooBarEntity::class);

        $receptionRouteListAction = $this->createMock(ReceptionRouteListActionInterface::class);
        $receptionRouteListAction->expects($count > 0 ? static::once() : static::never())
            ->method('list')
            ->willReturn([new ReceptionRouteListResult($this->createMock(RouteKeyInterface::class))]);

        $emissionActor = new EmissionActor(
            $this->createMock(JobDispatcherContract::class),
            $logger,
            $this->createMock(StorageKeyGeneratorContract::class),
            $receptionRouteListAction
        );
        $emissionActor->performEmission(
            new TypedMappingCollection(FooBarEntity::class, \array_fill(0, $count, $mapping)),
            new EmitterStack([new ThrowEmitter()], FooBarEntity::class),
            $this->createMock(EmitContextInterface::class)
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
