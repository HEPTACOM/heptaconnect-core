<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder;
use Heptacom\HeptaConnect\Core\Test\Fixture\FooBarEntity;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Heptacom\HeptaConnect\Core\Exploration\ExplorerStackBuilder
 */
class ExplorerStackBuilderTest extends TestCase
{
    public function testStackBuilderOrder(): void
    {
        $stackBuilder = new ExplorerStackBuilder(
            new ExplorerCollection(),
            new ExplorerCollection(),
            FooBarEntity::class,
            $this->createMock(LoggerInterface::class),
        );

        $calc = [];

        $explorer1 = $this->createMock(ExplorerContract::class);
        $explorer1->method('explore')
            ->willReturnCallback(
                static function (ExploreContextInterface $c, ExplorerStackInterface $s) use (&$calc): iterable {
                    $calc[] = 1;

                    return $s->next($c);
                }
            );
        $explorer1->method('supports')->willReturn(FooBarEntity::class);
        $explorer2 = $this->createMock(ExplorerContract::class);
        $explorer2->method('explore')
            ->willReturnCallback(
                static function (ExploreContextInterface $c, ExplorerStackInterface $s) use (&$calc): iterable {
                    $calc[] = 2;

                    return $s->next($c);
                }
            );
        $explorer2->method('supports')->willReturn(FooBarEntity::class);
        $stackBuilder->push($explorer1); // resembles source
        $stackBuilder->push($explorer2); // resembles decorators
        $stack = $stackBuilder->build();
        $stack->next($this->createMock(ExploreContextInterface::class));

        self::assertEquals([2, 1], $calc);
    }
}
