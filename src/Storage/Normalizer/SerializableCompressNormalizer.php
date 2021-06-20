<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

class SerializableCompressNormalizer extends SerializableNormalizer
{
    public function getType(): string
    {
        return parent::getType().'+gzpress';
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return \gzcompress(parent::normalize($object, $format, $context));
    }
}
