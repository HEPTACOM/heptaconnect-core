<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

final class SerializableCompressDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private DenormalizerInterface $serializableDenormalizer
    ) {
    }

    public function getType(): string
    {
        return $this->serializableDenormalizer->getType() . '+gzpress';
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new InvalidArgumentException();
        }

        return $this->serializableDenormalizer->denormalize(
            \gzuncompress($data),
            $this->serializableDenormalizer->getType(),
            $format,
            $context
        );
    }

    /**
     * @psalm-assert string $data
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === $this->getType()
            && \is_string($data)
            && $this->serializableDenormalizer->supportsDenormalization(
                \gzuncompress($data),
                $this->serializableDenormalizer->getType(),
                $format
            );
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }
}
