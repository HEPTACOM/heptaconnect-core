<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;

final class PreviewPortalNodeStorage implements PortalStorageInterface
{
    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        return false;
    }

    public function list(): iterable
    {
        return [];
    }

    public function has($key): bool
    {
        return false;
    }

    public function delete($key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $default;
        }
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    public function deleteMultiple($keys): bool
    {
        return false;
    }
}
