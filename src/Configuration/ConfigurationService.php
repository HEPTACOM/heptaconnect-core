<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigurationService implements ConfigurationServiceInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationStorageContract $storage;

    public function __construct(PortalRegistryInterface $portalRegistry, ConfigurationStorageContract $storage)
    {
        $this->portalRegistry = $portalRegistry;
        $this->storage = $storage;
    }

    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?array
    {
        $template = $this->getMergedConfigurationTemplate($portalNodeKey);

        if (\is_null($template)) {
            return null;
        }

        return $template->resolve($this->storage->getConfiguration($portalNodeKey));
    }

    public function setPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $configuration): void
    {
        $template = $this->getMergedConfigurationTemplate($portalNodeKey);

        if (\is_null($template)) {
            return;
        }

        $data = $this->storage->getConfiguration($portalNodeKey);
        $data = $this->removeStorageKeysWhenValueIsNull($data, $configuration ?? []);
        $data = \is_null($configuration) ? [] : \array_replace_recursive($data, $configuration);

        $template->resolve($data);
        $this->storage->setConfiguration($portalNodeKey, $data);
    }

    /**
     * @TODO extract for easier testing
     */
    private function removeStorageKeysWhenValueIsNull(array $editable, array $nullArray): array
    {
        foreach ($nullArray as $key => $value) {
            if (\is_array($key) && \array_key_exists($key, $editable)) {
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

    private function getMergedConfigurationTemplate(PortalNodeKeyInterface $portalNodeKey): ?OptionsResolver
    {
        $portal = $this->portalRegistry->getPortal($portalNodeKey);

        $template = $portal->getConfigurationTemplate();
        $extensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);

        foreach ($extensions as $extension) {
            $template = $extension->extendConfiguration($template);
        }

        return $template;
    }
}
