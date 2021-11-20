<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Exception\GzipCompressException;

class SerializableCompressNormalizer extends SerializableNormalizer
{
    public function getType(): string
    {
        return parent::getType().'+gzpress';
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        $result = \gzcompress(parent::normalize($object, $format, $context));

        if (!\is_string($result)) {
            throw new GzipCompressException(1637432095);
        }

        return $result;
    }
}
