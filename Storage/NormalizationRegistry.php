<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;

final class NormalizationRegistry extends NormalizationRegistryContract
{
    /**
     * @var NormalizerInterface[]
     */
    private readonly array $normalizer;

    /**
     * @var DenormalizerInterface[]
     */
    private readonly array $denormalizer;

    /**
     * @phpstan-param iterable<int, NormalizerInterface> $normalizer
     * @phpstan-param iterable<int, DenormalizerInterface> $denormalizer
     */
    public function __construct(iterable $normalizer, iterable $denormalizer)
    {
        $this->normalizer = \iterable_to_array($normalizer);
        $this->denormalizer = \iterable_to_array($denormalizer);
    }

    #[\Override]
    public function getNormalizer(mixed $value): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->supportsNormalization($value)) {
                return $normalizer;
            }
        }

        return parent::getNormalizer($value);
    }

    #[\Override]
    public function getNormalizerByType(string $type): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->getType() === $type) {
                return $normalizer;
            }
        }

        return parent::getNormalizerByType($type);
    }

    #[\Override]
    public function getDenormalizer(string $type): ?DenormalizerInterface
    {
        foreach ($this->denormalizer as $denormalizer) {
            if ($denormalizer->getType() === $type) {
                return $denormalizer;
            }
        }

        return parent::getDenormalizer($type);
    }
}
