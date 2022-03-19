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
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Listing\PortalNodeStorageListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\PortalNodeStorageItemContract;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItem;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItems;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageSetActionInterface;
use Psr\Log\LoggerInterface;

class PortalStorage implements PortalStorageInterface
{
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalNodeStorageClearActionInterface $portalNodeStorageClearAction;

    private PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction;

    private PortalNodeStorageGetActionInterface $portalNodeStorageGetAction;

    private PortalNodeStorageListActionInterface $portalNodeStorageListAction;

    private PortalNodeStorageSetActionInterface $portalNodeStorageSetAction;

    private LoggerInterface $logger;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalNodeStorageClearActionInterface $portalNodeStorageClearAction,
        PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction,
        PortalNodeStorageGetActionInterface $portalNodeStorageGetAction,
        PortalNodeStorageListActionInterface $portalNodeStorageListAction,
        PortalNodeStorageSetActionInterface $portalNodeStorageSetAction,
        LoggerInterface $logger,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalNodeStorageClearAction = $portalNodeStorageClearAction;
        $this->portalNodeStorageDeleteAction = $portalNodeStorageDeleteAction;
        $this->portalNodeStorageGetAction = $portalNodeStorageGetAction;
        $this->portalNodeStorageListAction = $portalNodeStorageListAction;
        $this->portalNodeStorageSetAction = $portalNodeStorageSetAction;
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
            $item = $this->packSetItem($key, $value, $ttl);

            if (!$item instanceof PortalNodeStorageSetItem) {
                return false;
            }

            $this->portalNodeStorageSetAction->set(new PortalNodeStorageSetPayload(
                $this->portalNodeKey,
                new PortalNodeStorageSetItems([$item])
            ));

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
        $criteria = new PortalNodeStorageListCriteria($this->portalNodeKey);

        try {
            foreach ($this->portalNodeStorageListAction->list($criteria) as $result) {
                $value = $this->unpackGetResult($result);

                if ($value === null) {
                    continue;
                }

                yield $result->getStorageKey() => $value;
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed listing values from the portal storage', [
                'code' => 1646383738,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
            ]);
        }
    }

    public function has($key): bool
    {
        try {
            $storageKeys = new StringCollection([(string) $key]);
            $getCriteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, $storageKeys);

            foreach ($this->portalNodeStorageGetAction->get($getCriteria) as $getResult) {
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
        $keysArray = \array_values(\array_map('strval', \iterable_to_array($keys)));
        $criteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, new StringCollection($keysArray));
        $notReturnedKeys = \array_fill_keys($keysArray, true);
        $deleteCriteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection([]));

        try {
            foreach ($this->portalNodeStorageGetAction->get($criteria) as $getResult) {
                unset($notReturnedKeys[$getResult->getStorageKey()]);

                $value = $this->unpackGetResult($getResult);

                if ($value === null) {
                    $deleteCriteria->getStorageKeys()->push([$getResult->getStorageKey()]);
                }

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

        $this->portalNodeStorageDeleteAction->delete($deleteCriteria);

        $notReturnedStorageKeys = \array_map('strval', \array_keys($notReturnedKeys));

        if ($notReturnedStorageKeys !== []) {
            foreach ($notReturnedStorageKeys as $key) {
                yield $key => $default;
            }
        }
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);
        $payload = new PortalNodeStorageSetPayload($this->portalNodeKey, new PortalNodeStorageSetItems());

        foreach ($values as $key => $value) {
            $item = $this->packSetItem($key, $value, $ttl);

            if (!$item instanceof PortalNodeStorageSetItem) {
                return false;
            }

            $payload->getSets()->push([$item]);
        }

        try {
            $this->portalNodeStorageSetAction->set($payload);

            return true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed storing multiple values in the portal storage', [
                'code' => 1631387363,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
            ]);

            return false;
        }
    }

    public function deleteMultiple($keys): bool
    {
        try {
            $criteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection(\array_values(\array_map('strval', \iterable_to_array($keys)))));
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

    private function unpackGetResult(PortalNodeStorageItemContract $getResult)
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

    private function packSetItem(string $key, $value, ?\DateInterval $ttl): ?PortalNodeStorageSetItem
    {
        $normalizer = $this->normalizationRegistry->getNormalizer($value);

        if (!$normalizer instanceof NormalizerInterface) {
            $this->logger->error('Failed getting a normalizer for a value for storing a value in the portal storage', [
                'code' => 1631562097,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return null;
        }

        try {
            $normalizedValue = $normalizer->normalize($value);
        } catch (\Throwable $throwable) {
            $this->logger->error('Failed normalizing a value for a value for storing it in the portal storage', [
                'code' => 1631562928,
                'exception' => $throwable,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return null;
        }

        if (!\is_string($normalizedValue)) {
            $this->logger->error('Normalization result of a value for storing a value in the portal storage is not a string', [
                'code' => 1631562285,
                'portalNodeKey' => $this->portalNodeKey,
                'key' => $key,
            ]);

            return null;
        }

        return new PortalNodeStorageSetItem($key, $normalizedValue, $normalizer->getType(), $ttl);
    }
}
