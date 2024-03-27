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

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$this->supportsDenormalization($data, $type, $format)) {
            throw new InvalidArgumentException();
        }

        $unserialize_callback_func = false;

        try {
            $unserialize_callback_func = \ini_get('unserialize_callback_func');
            \ini_set('unserialize_callback_func', self::class . '::handleUnserializeClass');

            $result = \unserialize($data);
        } catch (\Throwable) {
            return null;
        } finally {
            if (\is_string($unserialize_callback_func)) {
                \ini_set('unserialize_callback_func', $unserialize_callback_func);
            }
        }

        return $result;
    }

    /**
     * @psalm-assert string $data
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === $this->getType()
            && \is_string($data)
            && (\unserialize($data) !== false || $data === 'b:0;');
    }

    public static function handleUnserializeClass(): void
    {
        throw new \Exception();
    }

    public function getSupportedTypes(?string $format): array
    {
        return [$this->getType() => true];
    }
}
