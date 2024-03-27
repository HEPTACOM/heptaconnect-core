<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestDeserializerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

final class Psr7RequestDenormalizer implements DenormalizerInterface
{
    public function __construct(
        private RequestDeserializerInterface $deserializer
    ) {
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): RequestInterface
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new InvalidArgumentException();
        }

        return $this->deserializer->deserialize($data);
    }

    /**
     * @psalm-assert string $data
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if ($type !== $this->getType() || !\is_string($data)) {
            return false;
        }

        try {
            $this->denormalize($data, $type, $format);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }
}
