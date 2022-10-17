<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Parallelization;

use Heptacom\HeptaConnect\Core\Parallelization\Contract\ResourceLockStorageContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Exception\ResourceIsLockedException;
use Symfony\Component\Lock\LockFactory;

final class ResourceLockStorage extends ResourceLockStorageContract
{
    public function __construct(private LockFactory $lockFactory)
    {
    }

    public function create(string $key): void
    {
        try {
            if (!$this->lockFactory->createLock($key, 300.0, false)->acquire()) {
                throw new ResourceIsLockedException($key, null);
            }
        } catch (\Throwable) {
            throw new ResourceIsLockedException($key, null);
        }
    }

    public function has(string $key): bool
    {
        return $this->lockFactory->createLock($key, 300.0, false)->isAcquired();
    }

    public function delete(string $key): void
    {
        $this->lockFactory->createLock($key)->release();
    }
}
