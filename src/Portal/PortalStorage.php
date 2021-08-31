<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageContract $portalStorage,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
        $this->portalNodeKey = $portalNodeKey;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     * @throws NotFoundException
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function get($key, $default = null)
    {
        if (!$this->portalStorage->has($this->portalNodeKey, $key)) {
            return $default;
        }

        try {
            $value = $this->portalStorage->getValue($this->portalNodeKey, $key);
        } catch (NotFoundException $exception) {
            $this->portalStorage->unset($this->portalNodeKey, $key);

            return $default;
        }

        $type = $this->portalStorage->getType($this->portalNodeKey, $key);
        $denormalizer = $this->normalizationRegistry->getDenormalizer($type);

        if (!$denormalizer instanceof DenormalizerInterface) {
            return $default;
        }

        if (!$denormalizer->supportsDenormalization($value, $type)) {
            return $default;
        }

        $result = $denormalizer->denormalize($value, $type);

        if ($result === null) {
            $this->portalStorage->unset($this->portalNodeKey, $key);
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateInterval|null $ttl
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function set($key, $value, $ttl = null): void
    {
        $normalizer = $this->normalizationRegistry->getNormalizer($value);

        if (!$normalizer instanceof NormalizerInterface) {
            return;
        }

        $this->portalStorage->set(
            $this->portalNodeKey,
            $key,
            (string) $normalizer->normalize($value),
            $normalizer->getType(),
            $ttl
        );
    }

    public function list(): iterable
    {
        foreach ($this->portalStorage->list($this->portalNodeKey) as $key => $item) {
            $value = $item['value'];
            $type = $item['type'];

            $denormalizer = $this->normalizationRegistry->getDenormalizer($type);

            if (!$denormalizer instanceof DenormalizerInterface) {
                continue;
            }

            if (!$denormalizer->supportsDenormalization($value, $type)) {
                continue;
            }

            yield $key => $denormalizer->denormalize($value, $type);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     */
    public function has($key): bool
    {
        return $this->portalStorage->has($this->portalNodeKey, $key);
    }

    /**
     * @param string $key
     * @throws NotFoundException
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     */
    public function delete($key): void
    {
        $this->portalStorage->unset($this->portalNodeKey, $key);
    }

    /**
     * @deprecated
     */
    public function canGet(string $type): bool
    {
        return $this->normalizationRegistry->getDenormalizer($type) instanceof DenormalizerInterface;
    }

    /**
     * @deprecated
     */
    public function canSet(string $type): bool
    {
        return $this->normalizationRegistry->getNormalizerByType($type) instanceof NormalizerInterface;
    }

    /**
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     */
    public function clear()
    {
        $this->portalStorage->clear($this->portalNodeKey);
    }

    /**
     * @param array $keys
     * @param null $default
     * @return iterable
     * @throws NotFoundException
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return $this->portalStorage->getMultiple($this->portalNodeKey, $keys);
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool|void
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param iterable $keys
     * @return bool|void
     * @throws NotFoundException
     * @throws \Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException
     */
    public function deleteMultiple($keys)
    {
        $this->portalStorage->deleteMultiple($this->portalNodeKey, $keys);
    }
}
