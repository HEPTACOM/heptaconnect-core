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
use Psr\Log\LoggerInterface;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    private LoggerInterface $logger;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageContract $portalStorage,
        LoggerInterface $logger,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
        $this->logger = $logger;
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
                $this->logger->error('Failed retrieving a normalizer for a value from the portal storage', [
                    'code' => 1631565257,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

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
            $this->logger->error('Failed retrieving a value from the portal storage', [
                'code' => 1631561839,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return $default;
        }
    }

    public function set($key, $value, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);

        try {
            $normalizer = $this->normalizationRegistry->getNormalizer($value);

            if (!$normalizer instanceof NormalizerInterface) {
                $this->logger->error('Failed getting a normalizer for a value for storing a value in the portal storage', [
                    'code' => 1631565446,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

                return false;
            }

            $normalizedValue = $normalizer->normalize($value);

            if (!\is_scalar($normalizedValue)) {
                $this->logger->error('Failed normalizing a value for storing a value in the portal storage', [
                    'code' => 1631565376,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

                return false;
            }

            $this->portalStorage->set($this->portalNodeKey, $key, (string) $normalizedValue, $normalizer->getType(), $ttl);

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed storing a value in the portal storage', [
                'code' => 1631387510,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return false;
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
            $this->logger->error('Failed retrieving existence of a key in the portal storage', [
                'code' => 1631387470,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return false;
        }
    }

    public function delete($key): bool
    {
        try {
            $this->portalStorage->unset($this->portalNodeKey, $key);

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed deleting a key in the portal storage', [
                'code' => 1631387448,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return false;
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
        try {
            $this->portalStorage->clear($this->portalNodeKey);

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed clearing the portal storage', [
                'code' => 1631387430,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
            ]);

            return false;
        }
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        try {
            $keys = \iterable_to_array($keys);

            $result = $this->portalStorage->getMultiple($this->portalNodeKey, $keys);

            foreach ($result as $key => &$value) {                
                try {
                    $type = $this->portalStorage->getType($this->portalNodeKey, $key);
                } catch (NotFoundException $exception) {
                    unset($result[$key]);
                    
                    continue;
                }
                
                $denormalizer = $this->normalizationRegistry->getDenormalizer($type);

                if (!$denormalizer instanceof DenormalizerInterface) {
                    $this->logger->error('Failed retrieving a value from the portal storage', [
                        'code' => 1631563639,
                        'portalNodeKey' => $this->portalNodeKey,
                        'key' => $key,
                    ]);

                    continue;
                }

                if (!$denormalizer->supportsDenormalization($value, $type)) {
                    $this->logger->error('Failed normalizing a value from the portal storage', [
                        'code' => 1631563699,
                        'portalNodeKey' => $this->portalNodeKey,
                        'key' => $key,
                    ]);

                    $value = $default;

                    continue;
                }

                $value = $denormalizer->denormalize($value, $type);
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed getting multiple values from the portal storage', [
                'code' => 1631563058,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'keys' => $keys,
            ]);
        }

        foreach ($keys as $key) {
            if (!\array_key_exists($key, $result)) {
                $result[$key] = $default;
            }
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);

        $payload = [];

        foreach ($values as $key => $value) {
            $normalizer = $this->normalizationRegistry->getNormalizer($value);

            if (!$normalizer instanceof NormalizerInterface) {
                $this->logger->error('Failed storing a value out of many in the portal storage', [
                    'code' => 1631562097,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

                return false;
            }

            try {
                $normalizedValue = $normalizer->normalize($value);
            } catch (\Throwable $throwable) {
                $this->logger->error('Failed normalizing a value out of many for storing it in the portal storage', [
                    'code' => 1631562928,
                    'exception' => $throwable,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

                return false;
            }

            if (!\is_scalar($normalizedValue)) {
                $this->logger->error('Failed to normalize a value for storing it in the portal storage', [
                    'code' => 1631562285,
                    'portalNodeKey' => $this->portalNodeKey,
                    'key' => $key,
                ]);

                return false;
            }

            $payload[$normalizer->getType()][$key] = (string) $normalizedValue;
        }

        unset($key, $value);

        try {
            foreach ($payload as $type => $payloadItems) {
                foreach ($payloadItems as $key => $value) {
                    $this->portalStorage->set($this->portalNodeKey, (string) $key, $value, $type, $ttl);
                }
            }

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed storing multiple values in the portal storage', [
                'code' => 1631387363,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return false;
        }
    }

    public function deleteMultiple($keys): bool
    {
        try {
            $this->portalStorage->deleteMultiple($this->portalNodeKey, \iterable_to_array($keys));

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed deleting multiple keys in the portal storage', [
                'code' => 1631387202,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'keys' => $keys,
            ]);

            return false;
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
