<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Cache\CacheItemPoolInterface;

final class PortalNodeConfigurationCacheProcessor implements PortalNodeConfigurationProcessorInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache,
        private StorageKeyGeneratorContract $storageKeyGenerator
    ) {
    }

    public function read(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array
    {
        $cachedConfig = $this->cache->getItem($this->getConfigCacheKey($portalNodeKey));

        if ($cachedConfig->isHit()) {
            $configuration = $cachedConfig->get();

            if (\is_array($configuration)) {
                return $configuration;
            }
        }

        $configuration = $read();

        $this->cache->save($cachedConfig->set($configuration));

        return $configuration;
    }

    public function write(PortalNodeKeyInterface $portalNodeKey, array $payload, \Closure $write): void
    {
        $cachedConfigKey = $this->getConfigCacheKey($portalNodeKey);
        $cachedConfig = $this->cache->getItem($cachedConfigKey);

        if ($cachedConfig->isHit()) {
            $this->cache->deleteItem($cachedConfigKey);
        }

        $write($payload);
    }

    private function getConfigCacheKey(PortalNodeKeyInterface $portalNodeKey): string
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());
        $key = \str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '', $key);

        return 'config.cache.' . $key;
    }
}
