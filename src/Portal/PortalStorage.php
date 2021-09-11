<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Exception\PortalStorageExceptionWrapper;
use Heptacom\HeptaConnect\Core\Portal\Exception\PortalStorageNormalizationException;
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
                throw new PortalStorageNormalizationException($key, $value);
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

    public function set($key, $value, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);

        try {
            $normalizer = $this->normalizationRegistry->getNormalizer($value);

            if (!$normalizer instanceof NormalizerInterface) {
                throw new PortalStorageNormalizationException($key, $value);
            }

            $normalizedValue = $normalizer->normalize($value);

            if (!\is_scalar($normalizedValue)) {
                throw new PortalStorageNormalizationException($key, $value);
            }

            $this->portalStorage->set($this->portalNodeKey, $key, (string) $normalizedValue, $normalizer->getType(), $ttl);

            return true;
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

    public function delete($key): bool
    {
        try {
            $this->portalStorage->unset($this->portalNodeKey, $key);

            return true;
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

    public function clear(): bool
    {
        try  {
            $this->portalStorage->clear($this->portalNodeKey);
        } catch (\Throwable $throwable) {
            return false;
        }

        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        try {
            $keys = \iterable_to_array($keys);

            $result = $this->portalStorage->getMultiple($this->portalNodeKey, $keys);

            foreach ($keys as $key) {
                if (!\array_key_exists($key, $result)) {
                    $result[$key] = $default;
                }
            }

            return $result;
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);

        try {
            $payload = [];

            foreach ($values as $key => $value) {
                $normalizer = $this->normalizationRegistry->getNormalizer($value);

                if (!$normalizer instanceof NormalizerInterface) {
                    throw new PortalStorageNormalizationException($key, $value);
                }

                $normalizedValue = $normalizer->normalize($value);

                if (!\is_scalar($normalizedValue)) {
                    throw new PortalStorageNormalizationException($key, $value);
                }

                $payload[$normalizer->getType()][$key] = (string) $normalizedValue;
            }

            unset($key, $value);

            foreach ($payload as $type => $payloadItems) {
                foreach ($payloadItems as $key => $value) {
                    $this->portalStorage->set($this->portalNodeKey, (string) $key, $value, $type, $ttl);
                }
            }
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        try {
            $this->portalStorage->deleteMultiple($this->portalNodeKey, \iterable_to_array($keys));

            return true;
        } catch (\Throwable $throwable) {
            throw new PortalStorageExceptionWrapper(__METHOD__, $throwable);
        }
    }

    /**
     * @param \DateInterval|int|null $ttl
     */
    private function convertTtl($ttl): ?\DateInterval
    {
        if (!\is_integer($ttl)) {
            return $ttl;
        }

        try {
            return new \DateInterval(\sprintf('PT%dS', $ttl));
        } catch (\Throwable $throwable) {
            return null;
        }
    }
}
