<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface;

class NormalizationRegistry
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
     * @psalm-param iterable<int, \Heptacom\HeptaConnect\Core\Storage\Contract\NormalizerInterface> $normalizer
     * @psalm-param iterable<int, \Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface> $denormalizer
     */
    public function __construct(iterable $normalizer, iterable $denormalizer)
    {
        $this->normalizer = iterable_to_array($normalizer);
        $this->denormalizer = iterable_to_array($denormalizer);
    }

    public function getNormalizer($value): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->supportsNormalization($value)) {
                return $normalizer;
            }
        }

        return null;
    }

    public function getNormalizerByType(string $type): ?NormalizerInterface
    {
        foreach ($this->normalizer as $normalizer) {
            if ($normalizer->getType() === $type) {
                return $normalizer;
            }
        }

        return null;
    }

    public function getDenormalizer(string $type): ?DenormalizerInterface
    {
        foreach ($this->denormalizer as $denormalizer) {
            if ($denormalizer->getType() === $type) {
                return $denormalizer;
            }
        }

        return null;
    }
}
