<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

final class ScalarDenormalizer implements DenormalizerInterface
{
    /**
     * @psalm-return 'scalar'
     */
    public function getType(): string
    {
        return 'scalar';
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new InvalidArgumentException();
        }

        return \unserialize($data, ['allowed_classes' => false]);
    }

    /**
     * @psalm-assert string $data
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === $this->getType()
            && \is_string($data)
            && (\unserialize($data, ['allowed_classes' => false]) !== false || $data === 'b:0;');
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType()];
    }
}
