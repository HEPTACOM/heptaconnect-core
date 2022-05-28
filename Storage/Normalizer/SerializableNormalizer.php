<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

final class SerializableNormalizer implements NormalizerInterface
{
    /**
     * @psalm-return 'serializable'
     */
    public function getType(): string
    {
        return 'serializable';
    }

    /**
     * @return string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException();
        }

        return \serialize($object);
    }

    public function supportsNormalization($data, $format = null)
    {
        if ($data instanceof StreamInterface) {
            return false;
        }

        try {
            \serialize($data);

            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }
}
