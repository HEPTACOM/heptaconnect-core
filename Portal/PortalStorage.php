<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Clear\PortalNodeStorageClearCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Delete\PortalNodeStorageDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Psr\Log\LoggerInterface;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    private PortalNodeStorageClearActionInterface $portalNodeStorageClearAction;

    private PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction;

    private PortalNodeStorageGetActionInterface $portalNodeStorageGetAction;

    private LoggerInterface $logger;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageContract $portalStorage,
        PortalNodeStorageClearActionInterface $portalNodeStorageClearAction,
        PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction,
        PortalNodeStorageGetActionInterface $portalNodeStorageGetAction,
        LoggerInterface $logger,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
        $this->portalNodeStorageClearAction = $portalNodeStorageClearAction;
        $this->portalNodeStorageDeleteAction = $portalNodeStorageDeleteAction;
        $this->portalNodeStorageGetAction = $portalNodeStorageGetAction;
        $this->logger = $logger;
        $this->portalNodeKey = $portalNodeKey;
    }

    public function get($key, $default = null)
    {
        try {
            $storageKeys = new StringCollection([(string) $key]);
            $getCriteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, $storageKeys);
            /** @var PortalNodeStorageGetResult[] $getResults */
            $getResults = \iterable_to_array($this->portalNodeStorageGetAction->get($getCriteria));

            if ($getResults === []) {
                return $default;
            }

            /** @var PortalNodeStorageGetResult $getResult */
            $getResult = \current($getResults);
            $result = $this->unpackGetResult($getResult);

            if ($result === null) {
                $this->portalNodeStorageDeleteAction->delete(new PortalNodeStorageDeleteCriteria($this->portalNodeKey, $storageKeys));

                return $default;
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
            $storageKeys = new StringCollection([(string) $key]);
            $getCriteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, $storageKeys);

            foreach (\iterable_to_array($this->portalNodeStorageGetAction->get($getCriteria)) as $getResult) {
                return $this->unpackGetResult($getResult) !== null;
            }

            return false;
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
            $criteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection([(string) $key]));
            $this->portalNodeStorageDeleteAction->delete($criteria);

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
            $criteria = new PortalNodeStorageClearCriteria($this->portalNodeKey);
            $this->portalNodeStorageClearAction->clear($criteria);

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
        /** @var array<int, string> $keysArray */
        $keysArray = \array_values(\iterable_to_array($keys));
        $criteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, new StringCollection($keysArray));
        $notReturnedKeys = \array_fill_keys($keysArray, true);

        try {
            foreach ($this->portalNodeStorageGetAction->get($criteria) as $getResult) {
                unset($notReturnedKeys[$getResult->getStorageKey()]);

                $value = $this->unpackGetResult($getResult);

                yield $getResult->getStorageKey() => $value ?? $default;
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed getting multiple values from the portal storage', [
                'code' => 1631563058,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'keys' => $keys,
            ]);
        }

        foreach (\array_keys($notReturnedKeys) as $key) {
            yield $key => $default;
        }
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
            $criteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection($keys));
            $this->portalNodeStorageDeleteAction->delete($criteria);

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
        if (!\is_int($ttl)) {
            return $ttl;
        }

        try {
            return new \DateInterval(\sprintf('PT%dS', $ttl));
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    private function unpackGetResult(PortalNodeStorageGetResult $getResult)
    {
        $denormalizer = $this->normalizationRegistry->getDenormalizer($getResult->getType());

        if (!$denormalizer instanceof DenormalizerInterface) {
            $this->logger->error('Failed retrieving a normalizer for a value from the portal storage', [
                'code' => 1631565257,
                'portalNodeKey' => $getResult->getPortalNodeKey(),
                'key' => $getResult->getStorageKey(),
            ]);

            return null;
        }

        if (!$denormalizer->supportsDenormalization($getResult->getValue(), $getResult->getType())) {
            return null;
        }

        return $denormalizer->denormalize($getResult->getValue(), $getResult->getType());
    }
}
