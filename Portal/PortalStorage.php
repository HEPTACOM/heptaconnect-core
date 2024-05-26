<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Storage\Contract\PortalNodeStorageItemPackerInterface;
use Heptacom\HeptaConnect\Core\Portal\Storage\Contract\PortalNodeStorageItemUnpackerInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Clear\PortalNodeStorageClearCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Delete\PortalNodeStorageDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetResult;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Listing\PortalNodeStorageListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItem;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItems;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageSetActionInterface;
use Heptacom\HeptaConnect\Utility\Collection\Scalar\StringCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

final readonly class PortalStorage implements PortalStorageInterface
{
    public function __construct(
        private PortalNodeStorageItemPackerInterface $storageItemPacker,
        private PortalNodeStorageItemUnpackerInterface $storageItemUnpacker,
        private PortalNodeStorageClearActionInterface $storageClearAction,
        private PortalNodeStorageDeleteActionInterface $storageDeleteAction,
        private PortalNodeStorageGetActionInterface $storageGetAction,
        private PortalNodeStorageListActionInterface $storageListAction,
        private PortalNodeStorageSetActionInterface $storageSetAction,
        private LoggerInterface $logger,
        private PortalNodeKeyInterface $portalNodeKey
    ) {
    }

    #[\Override]
    public function get($key, $default = null)
    {
        try {
            $storageKeys = new StringCollection([(string) $key]);
            $getCriteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, $storageKeys);
            /** @var PortalNodeStorageGetResult[] $getResults */
            $getResults = \iterable_to_array($this->storageGetAction->get($getCriteria));

            if ($getResults === []) {
                return $default;
            }

            /** @var PortalNodeStorageGetResult $getResult */
            $getResult = \current($getResults);
            $result = $this->storageItemUnpacker->unpack($getResult);

            if ($result === null) {
                $this->storageDeleteAction->delete(new PortalNodeStorageDeleteCriteria($this->portalNodeKey, $storageKeys));

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

    #[\Override]
    public function set($key, $value, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);

        try {
            $item = $this->storageItemPacker->pack($key, $value, $ttl);

            if (!$item instanceof PortalNodeStorageSetItem) {
                return false;
            }

            $this->storageSetAction->set(new PortalNodeStorageSetPayload(
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

    #[\Override]
    public function list(): iterable
    {
        $criteria = new PortalNodeStorageListCriteria($this->portalNodeKey);

        try {
            foreach ($this->storageListAction->list($criteria) as $result) {
                try {
                    $value = $this->storageItemUnpacker->unpack($result);
                } catch (\Throwable $throwable) {
                    $this->logger->error('Failed unpack a portal storage value for listing', [
                        'code' => 1651338559,
                        'exception' => $throwable,
                        'portalNodeKey' => $this->portalNodeKey,
                    ]);

                    continue;
                }

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

    #[\Override]
    public function has($key): bool
    {
        try {
            $storageKeys = new StringCollection([(string) $key]);
            $getCriteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, $storageKeys);

            foreach ($this->storageGetAction->get($getCriteria) as $getResult) {
                return $this->storageItemUnpacker->unpack($getResult) !== null;
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

    #[\Override]
    public function delete($key): bool
    {
        try {
            $criteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection([(string) $key]));
            $this->storageDeleteAction->delete($criteria);

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

    #[\Override]
    public function clear(): bool
    {
        try {
            $criteria = new PortalNodeStorageClearCriteria($this->portalNodeKey);
            $this->storageClearAction->clear($criteria);

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

    #[\Override]
    public function getMultiple($keys, $default = null): iterable
    {
        $keysArray = $this->validateKeys($keys);
        $criteria = new PortalNodeStorageGetCriteria($this->portalNodeKey, new StringCollection($keysArray));
        $notReturnedKeys = \array_fill_keys($keysArray, true);
        $deleteCriteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection([]));

        try {
            foreach ($this->storageGetAction->get($criteria) as $getResult) {
                unset($notReturnedKeys[$getResult->getStorageKey()]);

                $value = $this->storageItemUnpacker->unpack($getResult);

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

        $this->storageDeleteAction->delete($deleteCriteria);

        $notReturnedKeys = \array_map('strval', \array_keys($notReturnedKeys));

        if ($notReturnedKeys !== []) {
            foreach ($notReturnedKeys as $key) {
                yield $key => $default;
            }
        }
    }

    #[\Override]
    public function setMultiple($values, $ttl = null): bool
    {
        $ttl = $this->convertTtl($ttl);
        $payload = new PortalNodeStorageSetPayload($this->portalNodeKey, new PortalNodeStorageSetItems());

        foreach ($values as $key => $value) {
            $item = $this->storageItemPacker->pack((string) $key, $value, $ttl);

            if (!$item instanceof PortalNodeStorageSetItem) {
                return false;
            }

            $payload->getSets()->push([$item]);
        }

        try {
            $this->storageSetAction->set($payload);

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

    #[\Override]
    public function deleteMultiple($keys): bool
    {
        try {
            $criteria = new PortalNodeStorageDeleteCriteria($this->portalNodeKey, new StringCollection($this->validateKeys($keys)));
            $this->storageDeleteAction->delete($criteria);

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

    private function convertTtl(\DateInterval|int|null $ttl): ?\DateInterval
    {
        if (!\is_int($ttl)) {
            return $ttl;
        }

        try {
            return new \DateInterval(\sprintf('PT%dS', $ttl));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return array<int, string>
     */
    private function validateKeys(iterable $keys): array
    {
        /** @var array<int, string> $keysArray */
        $keysArray = [];

        foreach ($keys as $key) {
            if (!\is_string($key)) {
                throw new InvalidArgumentException();
            }

            $keysArray[] = $key;
        }

        return $keysArray;
    }
}
