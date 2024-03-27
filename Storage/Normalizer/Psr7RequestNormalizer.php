<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\RequestSerializerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class Psr7RequestNormalizer implements NormalizerInterface
{
    public function __construct(
        private RequestSerializerInterface $serializer
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof RequestInterface;
    }

    public function getType(): string
    {
        return 'psr7-request';
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        if (!$object instanceof RequestInterface) {
            throw new InvalidArgumentException(
                'Psr7RequestNormalizer can only normalize request objects. Got: ' . $object::class,
                1647789809
            );
        }

        return $this->serializer->serialize($object);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }
}
