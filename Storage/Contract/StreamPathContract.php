<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Contract;

class StreamPathContract
{
    public const STORAGE_LOCATION = '42c5acf20a7011eba428f754dbb80254';

    public function buildPath(string $filename): string
    {
        $prefix = \array_slice(\str_split($filename, 2), 0, 3);
        $parts = [self::STORAGE_LOCATION, ...$prefix, $filename];

        return \implode('/', $parts);
    }
}
