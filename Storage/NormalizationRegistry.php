<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;

class NormalizationRegistry extends NormalizationRegistryContract
{
    /**
     * @var array|NormalizerInterface[]
     */
    private array $normalizer;

    /**
     * @var array|DenormalizerInterface[]
     */
    private array $denormalizer;

    /**
     * @psalm-param iterable<int, \Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface> $normalizer
     * @psalm-param iterable<int, \Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface> $denormalizer
     */
    public function __construct(iterable $normalizer, iterable $denormalizer)
    {
        $this->normalizer = \iterable_to_array($normalizer);
        $this->denormalizer = \iterable_to_array($denormalizer);
    }

    public function getNormalizer($value): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->supportsNormalization($value)) {
                return $normalizer;
            }
        }

        return parent::getNormalizer($value);
    }

    public function getNormalizerByType(string $type): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->getType() === $type) {
                return $normalizer;
            }
        }

        return parent::getNormalizerByType($type);
    }

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
