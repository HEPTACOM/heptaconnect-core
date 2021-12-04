<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Reception\Support;

use Heptacom\HeptaConnect\Portal\Base\Reception\Support\PostProcessorDataBag;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Portal\Base\Reception\Support\PostProcessorDataBag
 */
class PostProcessorDataBagTest extends TestCase
{
    public function testPriorityIndependent(): void
    {
        $first = new \stdClass();
        $first->index = 100;

        $second = new \stdClass();
        $second->index = -100;

        $bag = new PostProcessorDataBag();
        $bag->add($first, $first->index);
        $bag->add($second, $second->index);

        $items = \iterable_to_array($bag->of(\stdClass::class));
        static::assertSame($first, $items[0]);
        static::assertSame($second, $items[1]);

        $bag = new PostProcessorDataBag();
        $bag->add($second, $second->index);
        $bag->add($first, $first->index);

        $items = \iterable_to_array($bag->of(\stdClass::class));
        static::assertSame($first, $items[0]);
        static::assertSame($second, $items[1]);
    }
}
