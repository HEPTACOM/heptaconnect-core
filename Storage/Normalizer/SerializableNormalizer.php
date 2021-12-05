<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class SerializableNormalizer implements NormalizerInterface
{
    public function getType(): string
    {
        return 'serializable';
    }

    /**
     * @return string
     */
    public function normalize($object, ?string $format = null, array $context = [])
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException();
        }

        return \serialize($object);
    }

    public function supportsNormalization($data, ?string $format = null)
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
