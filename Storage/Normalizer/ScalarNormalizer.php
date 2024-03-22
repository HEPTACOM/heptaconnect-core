<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

final class ScalarNormalizer implements NormalizerInterface
{
    /**
     * @psalm-return 'scalar'
     */
    public function getType(): string
    {
        return 'scalar';
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
        return \is_bool($data) || \is_string($data) || $data === null || \is_float($data) || \is_int($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType()];
    }
}
