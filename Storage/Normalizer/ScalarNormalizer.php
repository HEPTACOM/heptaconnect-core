<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

class ScalarNormalizer implements NormalizerInterface
{
    public function getType(): string
    {
        return 'scalar';
    }

    /**
     * @return string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$this->supportsNormalization($object)) {
            throw new InvalidArgumentException();
        }

        return \serialize($object);
    }

    public function supportsNormalization($data, $format = null)
    {
        return \is_bool($data) || \is_string($data) || \is_null($data) || \is_float($data) || \is_int($data);
    }
}
