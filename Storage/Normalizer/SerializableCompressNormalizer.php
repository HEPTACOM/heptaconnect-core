<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Exception\GzipCompressException;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;

final class SerializableCompressNormalizer implements NormalizerInterface
{
    private NormalizerInterface $serializableNormalizer;

    public function __construct(NormalizerInterface $serializableNormalizer)
    {
        $this->serializableNormalizer = $serializableNormalizer;
    }

    public function getType(): string
    {
        return $this->serializableNormalizer->getType() . '+gzpress';
    }

    /**
     * @param string|null $format
     */
    public function normalize($object, $format = null, array $context = []): string
    {
        $compressedValue = $this->serializableNormalizer->normalize($object, $format, $context);

        if (!\is_string($compressedValue)) {
            throw new GzipCompressException(1637432096);
        }

        $result = \gzcompress($compressedValue);

        if (!\is_string($result)) {
            throw new GzipCompressException(1637432095);
        }

        return $result;
    }

    /**
     * @param string|null $format
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->serializableNormalizer->supportsNormalization($data, $format);
    }
}
