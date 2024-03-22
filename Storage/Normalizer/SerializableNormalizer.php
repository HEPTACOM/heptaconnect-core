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

    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException();
        }

        return \serialize($object);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($data instanceof StreamInterface) {
            return false;
        }

        try {
            \serialize($data);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType()];
    }
}
