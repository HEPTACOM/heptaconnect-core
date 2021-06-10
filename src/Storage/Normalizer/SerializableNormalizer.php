<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class SerializableNormalizer implements NormalizerInterface
{
    public function getType(): string
    {
        return 'serializable';
    }

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
