<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ScalarDenormalizer implements DenormalizerInterface
{
    public function getType(): string
    {
        return 'scalar';
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $type)) {
            throw new InvalidArgumentException();
        }

        return \unserialize($data, ['allowed_classes' => false]);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === $this->getType()
            && \is_string($data)
            && (\unserialize($data, ['allowed_classes' => false]) !== false || $data === 'b:0;');
    }
}
