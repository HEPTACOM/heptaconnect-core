<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Emission;

use Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Emission\EmitterStackBuilder
 */
class EmitterStackBuilderTest extends TestCase
{
    public function testStackBuilderOrder(): void
    {
        $stackBuilder = new EmitterStackBuilder(
            $this->createMock(PortalRegistryInterface::class),
            $this->createMock(PortalNodeKeyInterface::class),
            '',
        );

        $calc = [];

        $emitter1 = $this->createMock(EmitterContract::class);
        $emitter1->method('emit')
            ->willReturnCallback(
                static function (MappingCollection $m, EmitContextInterface $c, EmitterStackInterface $s) use (&$calc): iterable {
                    $calc[] = 1;
                    return $s->next($m, $c);
                }
            );
        $emitter2 = $this->createMock(EmitterContract::class);
        $emitter2->method('emit')
            ->willReturnCallback(
                static function (MappingCollection $m, EmitContextInterface $c, EmitterStackInterface $s) use (&$calc): iterable {
                    $calc[] = 2;
                    return $s->next($m, $c);
                }
            );
        $stackBuilder->push($emitter1); // resembles source
        $stackBuilder->push($emitter2); // resembles decorators
        $stack = $stackBuilder->build();
        $stack->next(new MappingCollection(), $this->createMock(EmitContextInterface::class));

        self::assertEquals([2, 1], $calc);
    }
}
