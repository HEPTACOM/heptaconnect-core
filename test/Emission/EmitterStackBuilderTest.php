<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Emission;

use Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder
 */
class EmitterStackBuilderTest extends TestCase
{
    public function testStackBuilderOrder(): void
    {
        $stackBuilder = new EmitterStackBuilder(
            new EmitterCollection(),
            new EmitterCollection(),
            FooBarEntity::class,
            $this->createMock(LoggerInterface::class),
        );

        $calc = [];

        $emitter1 = $this->createMock(EmitterContract::class);
        $emitter1->method('emit')
            ->willReturnCallback(
                static function (iterable $ids, EmitContextInterface $c, EmitterStackInterface $s) use (&$calc): iterable {
                    $calc[] = 1;

                    return $s->next($ids, $c);
                }
            );
        $emitter1->method('supports')->willReturn(FooBarEntity::class);

        $emitter2 = $this->createMock(EmitterContract::class);
        $emitter2->method('emit')
            ->willReturnCallback(
                static function (iterable $ids, EmitContextInterface $c, EmitterStackInterface $s) use (&$calc): iterable {
                    $calc[] = 2;

                    return $s->next($ids, $c);
                }
            );
        $emitter2->method('supports')->willReturn(FooBarEntity::class);
        $stackBuilder->push($emitter1); // resembles source
        $stackBuilder->push($emitter2); // resembles decorators
        $stack = $stackBuilder->build();
        $stack->next([], $this->createMock(EmitContextInterface::class));

        self::assertEquals([2, 1], $calc);
    }
}
