<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Storage;

use Heptacom\HeptaConnect\Core\Storage\Contract\StreamPathContract;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamDenormalizer;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamDenormalizer
 */
class StreamDenormalizerTest extends TestCase
{
    public function testNullStream(): void
    {
        $denorm = new StreamDenormalizer(new Filesystem(new NullAdapter()), new StreamPathContract());

        static::assertFalse($denorm->supportsDenormalization(null, $denorm->getType()));
        static::expectExceptionMessage('data is null');
        $denorm->denormalize(null, $denorm->getType());
    }

    public function testEmptyStreamReference(): void
    {
        $denorm = new StreamDenormalizer(new Filesystem(new NullAdapter()), new StreamPathContract());

        static::assertFalse($denorm->supportsDenormalization("", $denorm->getType()));
        static::expectExceptionMessage('data is empty');
        $denorm->denormalize("", $denorm->getType());
    }
}
