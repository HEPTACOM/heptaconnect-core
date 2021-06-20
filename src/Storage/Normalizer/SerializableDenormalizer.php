<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

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

        try {
            $unserialize_callback_func = \ini_get('unserialize_callback_func');
            \ini_set('unserialize_callback_func', __CLASS__.'::handleUnserializeClass');

            $result = \unserialize($data);
        } catch (\Throwable $exception) {
            return null;
        } finally {
            \ini_set('unserialize_callback_func', $unserialize_callback_func);
        }

        return $result;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === $this->getType()
            && \is_string($data)
            && (\unserialize($data) !== false || $data === 'b:0;');
    }

    public static function handleUnserializeClass(): void
    {
        throw new \Exception();
    }
}
