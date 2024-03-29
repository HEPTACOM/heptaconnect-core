<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Parallelization;

use Heptacom\HeptaConnect\Core\Parallelization\Contract\ResourceLockStorageContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Exception\ResourceIsLockedException;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

final class ResourceLocking extends ResourceLockingContract
{
    public function __construct(
        private ResourceLockStorageContract $resourceLockStorage,
        private StorageKeyGeneratorContract $storageKeyGenerator
    ) {
    }

    public function isLocked(string $resourceKey, ?StorageKeyInterface $owner): bool
    {
        return $this->resourceLockStorage->has($this->buildKey($resourceKey, $owner));
    }

    public function lock(string $resourceKey, ?StorageKeyInterface $owner): void
    {
        $key = $this->buildKey($resourceKey, $owner);

        if ($this->resourceLockStorage->has($key)) {
            throw new ResourceIsLockedException($key, $owner);
        }

        $this->resourceLockStorage->create($key);
    }

    public function release(string $resourceKey, ?StorageKeyInterface $owner): void
    {
        $key = $this->buildKey($resourceKey, $owner);

        if ($this->resourceLockStorage->has($key)) {
            $this->resourceLockStorage->delete($key);
        }
    }

    private function buildKey(string $resourceKey, ?StorageKeyInterface $owner): string
    {
        $prefix = 'ownerless';

        if ($owner instanceof StorageKeyInterface) {
            try {
                $prefix = $this->storageKeyGenerator->serialize($owner);
            } catch (UnsupportedStorageKeyException) {
            }
        }

        return $prefix . $resourceKey;
    }
}
