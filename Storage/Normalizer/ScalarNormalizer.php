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

    /**
     * @param string|null $format
     *
     * @return string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException();
        }

        return \serialize($object);
    }

    /**
     * @param string|null $format
     */
    public function supportsNormalization($data, $format = null)
    {
        return \is_bool($data) || \is_string($data) || $data === null || \is_float($data) || \is_int($data);
    }
}
