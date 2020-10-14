<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Core\Storage\Contract\DenormalizerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class SerializableDenormalizer implements DenormalizerInterface
{
    public function getType(): string
    {
        return 'serializable';
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $type)) {
            throw new InvalidArgumentException();
        }

        return \unserialize($data);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === $this->getType()
            && \is_string($data)
            && (\unserialize($data) !== false || $data === 'b:0;');
    }
}
