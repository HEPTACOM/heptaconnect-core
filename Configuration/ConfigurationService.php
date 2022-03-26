<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationService implements ConfigurationServiceInterface
{
    private PortalRegistryInterface $portalRegistry;

    private CacheItemPoolInterface $cache;

    private StorageKeyGeneratorContract $keyGenerator;

    private PortalNodeConfigurationGetActionInterface $portalNodeConfigurationGet;

    private PortalNodeConfigurationSetActionInterface $portalNodeConfigurationSet;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        CacheItemPoolInterface $cache,
        StorageKeyGeneratorContract $keyGenerator,
        PortalNodeConfigurationGetActionInterface $portalNodeConfigurationGet,
        PortalNodeConfigurationSetActionInterface $portalNodeConfigurationSet
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->cache = $cache;
        $this->keyGenerator = $keyGenerator;
        $this->portalNodeConfigurationGet = $portalNodeConfigurationGet;
        $this->portalNodeConfigurationSet = $portalNodeConfigurationSet;
    }

    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?array
    {
        $cachedConfig = $this->cache->getItem($this->getConfigCacheKey($portalNodeKey));

        if (!$cachedConfig->isHit()) {
            $template = $this->getMergedConfigurationTemplate($portalNodeKey);
            $config = $template->resolve($this->getPortalNodeConfigurationInternal($portalNodeKey));

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

        if ($configuration === null) {
            $data = null;
        } else {
            $data = $this->getPortalNodeConfigurationInternal($portalNodeKey);
            $data = $this->removeStorageKeysWhenValueIsNull($data, $configuration ?? []);
            $configuration = $this->removeStorageKeysWhenValueIsNull($configuration, $configuration ?? []);
            $data = \array_replace_recursive($data, $configuration);

            $template->resolve($data);
        }

        $this->portalNodeConfigurationSet->set(new PortalNodeConfigurationSetPayloads([
            new PortalNodeConfigurationSetPayload($portalNodeKey, $data),
        ]));
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

            if ($value !== null) {
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
        $key = $this->keyGenerator->serialize($portalNodeKey->withoutAlias());
        $key = \str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '', $key);

        return 'config.cache.' . $key;
    }

    private function getPortalNodeConfigurationInternal(PortalNodeKeyInterface $portalNodeKey): array
    {
        $criteria = new PortalNodeConfigurationGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeConfigurationGet->get($criteria) as $configuration) {
            return $configuration->getValue();
        }

        return [];
    }
}
