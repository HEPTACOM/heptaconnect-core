<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;

final class PreviewPortalNodeStorage implements PortalStorageInterface
{
    #[\Override]
    public function get($key, $default = null)
    {
        return $default;
    }

    #[\Override]
    public function set($key, $value, $ttl = null): bool
    {
        return false;
    }

    #[\Override]
    public function list(): iterable
    {
        return [];
    }

    #[\Override]
    public function has($key): bool
    {
        return false;
    }

    #[\Override]
    public function delete($key): bool
    {
        return false;
    }

    #[\Override]
    public function clear(): bool
    {
        return false;
    }

    #[\Override]
    public function getMultiple($keys, $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $default;
        }
    }

    #[\Override]
    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    #[\Override]
    public function deleteMultiple($keys): bool
    {
        return false;
    }
}
