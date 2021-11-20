<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationService implements ConfigurationServiceInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationStorageContract $storage;

    private CacheItemPoolInterface $cache;

    private StorageKeyGeneratorContract $keyGenerator;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationStorageContract $storage,
        CacheItemPoolInterface $cache,
        StorageKeyGeneratorContract $keyGenerator
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->storage = $storage;
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator;
    }

    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?array
    {
        $cachedConfig = $this->cache->getItem($this->getConfigCacheKey($portalNodeKey));

        if (!$cachedConfig->isHit()) {
            $template = $this->getMergedConfigurationTemplate($portalNodeKey);
            $config = $template->resolve($this->storage->getConfiguration($portalNodeKey));

            $this->cache->save($cachedConfig->set($config));
        } else {
            $config = $cachedConfig->get();
        }

        return $config;
    }

    public function setPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $configuration): void
    {
        $cachedConfigKey = $this->getConfigCacheKey($portalNodeKey);
        $cachedConfig = $this->cache->getItem($cachedConfigKey);

        if ($cachedConfig->isHit()) {
            $this->cache->deleteItem($cachedConfigKey);
        }

        $template = $this->getMergedConfigurationTemplate($portalNodeKey);

        if (\is_null($configuration)) {
            $data = null;
        } else {
            $data = $this->storage->getConfiguration($portalNodeKey);
            $data = $this->removeStorageKeysWhenValueIsNull($data, $configuration ?? []);
            $configuration = $this->removeStorageKeysWhenValueIsNull($configuration, $configuration ?? []);
            $data = \array_replace_recursive($data, $configuration);

            $template->resolve($data);
        }

        $this->storage->setConfiguration($portalNodeKey, $data);
    }

    /**
     * @TODO extract for easier testing
     */
    private function removeStorageKeysWhenValueIsNull(array $editable, array $nullArray): array
    {
        foreach ($nullArray as $key => $value) {
            if (\is_array($value) && \array_key_exists($key, $editable)) {
                $editable[$key] = $this->removeStorageKeysWhenValueIsNull($editable[$key], $value);
                continue;
            }

            if (!\is_null($value)) {
                continue;
            }

            unset($editable[$key]);
        }

        return $editable;
    }

    private function getMergedConfigurationTemplate(PortalNodeKeyInterface $portalNodeKey): OptionsResolver
    {
        $portal = $this->portalRegistry->getPortal($portalNodeKey);

        $template = $portal->getConfigurationTemplate();
        $extensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);

        foreach ($extensions as $extension) {
            $template = $extension->extendConfiguration($template);
        }

        return $template;
    }

    private function getConfigCacheKey(PortalNodeKeyInterface $portalNodeKey): string
    {
        $key = $this->keyGenerator->serialize($portalNodeKey);
        $key = \str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '', $key);

        return 'config.cache.' . $key;
    }
}
