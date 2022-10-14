<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Parallelization\Contract;

use Heptacom\HeptaConnect\Portal\Base\Parallelization\Exception\ResourceIsLockedException;

/**
 * Describes a locking infrastructure by key.
 */
abstract class ResourceLockStorageContract
{
    /**
     * Creates a lock with the given key.
     *
     * @throws ResourceIsLockedException
     */
    abstract public function create(string $key): void;

    /**
     * Checks, whether a lock by the given key is already locked.
     */
    abstract public function has(string $key): bool;

    /**
     * Unlocks a lock by the given key.
     */
    abstract public function delete(string $key): void;
}
