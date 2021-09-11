<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\PortalStorageExceptionWrapper;
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

    public function get($key, $default = null)
    {
        try {
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
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }

    public function set($key, $value, $ttl = null): void
    {
        try {
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
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
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

    public function has($key): bool
    {
        try {
            return $this->portalStorage->has($this->portalNodeKey, $key);
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }

    public function delete($key): void
    {
        try {
            $this->portalStorage->unset($this->portalNodeKey, $key);
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
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

    public function clear()
    {
        $this->portalStorage->clear($this->portalNodeKey);
    }

    public function getMultiple($keys, $default = null): iterable
    {
        try {
            return $this->portalStorage->getMultiple($this->portalNodeKey, $keys);
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function deleteMultiple($keys)
    {
        try {
            $this->portalStorage->deleteMultiple($this->portalNodeKey, $keys);
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }
}
