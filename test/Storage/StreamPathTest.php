<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Storage;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract
 */
class StreamPathTest extends TestCase
{
    public function testContract(): void
    {
        $testValue = 'fab0c9b5-40d4-439a-a3c9-9fe8bdc33676';

        $streamPath = new class ($testValue) extends StreamPathContract
        {
            private string $prefix;

            public function __construct(string $prefix)
            {
                $this->prefix = $prefix;
            }

            public function buildPath(string $filename): string
            {
                return $this->prefix . $filename;
            }
        };

        static::assertSame($testValue . 'foobar', $streamPath->buildPath('foobar'));
    }
}
