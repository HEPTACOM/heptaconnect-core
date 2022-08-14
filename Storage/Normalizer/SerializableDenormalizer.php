<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Normalizer;

use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Exception\InvalidArgumentException;

final class SerializableDenormalizer implements DenormalizerInterface
{
    /**
     * @psalm-return 'serializable'
     */
    public function getType(): string
    {
        return 'serializable';
    }

    /**
     * @param string|null $format
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!$this->supportsDenormalization($data, $type)) {
            throw new InvalidArgumentException();
        }

        $unserialize_callback_func = false;

        try {
            $unserialize_callback_func = \ini_get('unserialize_callback_func');
            \ini_set('unserialize_callback_func', self::class . '::handleUnserializeClass');

            $result = \unserialize($data);
        } catch (\Throwable $exception) {
            return null;
        } finally {
            if (\is_string($unserialize_callback_func)) {
                \ini_set('unserialize_callback_func', $unserialize_callback_func);
            }
        }

        return $result;
    }

    /**
     * @param string|null $format
     */
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
