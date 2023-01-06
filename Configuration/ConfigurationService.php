<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * @var PortalNodeConfigurationProcessorInterface[]
     */
    private array $configurationProcessors;

    /**
     * @param iterable<PortalNodeConfigurationProcessorInterface> $configurationProcessors
     */
    public function __construct(
        private PortalRegistryInterface $portalRegistry,
        private PortalNodeConfigurationGetActionInterface $portalNodeConfigurationGet,
        private PortalNodeConfigurationSetActionInterface $portalNodeConfigurationSet,
        iterable $configurationProcessors
    ) {
        $this->configurationProcessors = \iterable_to_array($configurationProcessors);
    }

    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?array
    {
        $template = $this->getMergedConfigurationTemplate($portalNodeKey);
        $configuration = $this->processReadConfiguration(
            $portalNodeKey,
            fn () => $this->getPortalNodeConfigurationInternal($portalNodeKey)
        );

        return $template->resolve($configuration);
    }

    public function setPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $configuration): void
    {
        $template = $this->getMergedConfigurationTemplate($portalNodeKey);

        if ($configuration === null) {
            $data = null;
        } else {
            $data = $this->getPortalNodeConfigurationInternal($portalNodeKey);
            $data = $this->removeStorageKeysWhenValueIsNull($data, $configuration);
            $configuration = $this->removeStorageKeysWhenValueIsNull($configuration, $configuration);
            $data = \array_replace_recursive($data, $configuration);

            $template->resolve($data);
        }

        $this->processWriteConfiguration($portalNodeKey, $data);
    }

    /**
     * @TODO extract for easier testing
     */
    private function removeStorageKeysWhenValueIsNull(array $editable, array $nullArray): array
    {
        foreach ($nullArray as $key => $value) {
            if (\is_array($value) && \array_key_exists($key, $editable) && \is_array($editable[$key])) {
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

    private function getPortalNodeConfigurationInternal(PortalNodeKeyInterface $portalNodeKey): array
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return [];
        }

        $criteria = new PortalNodeConfigurationGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->portalNodeConfigurationGet->get($criteria) as $configuration) {
            return $configuration->getValue();
        }

        return [];
    }

    /**
     * @param \Closure(): array $read
     */
    private function processReadConfiguration(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array
    {
        foreach ($this->configurationProcessors as $configurationProcessor) {
            $readConfiguration = $read;
            $read = static fn (): array => $configurationProcessor->read($portalNodeKey, $readConfiguration);
        }

        return $read();
    }

    private function processWriteConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $configuration): void
    {
        $write = function (array $config) use ($portalNodeKey): void {
            $this->portalNodeConfigurationSet->set(new PortalNodeConfigurationSetPayloads([
                new PortalNodeConfigurationSetPayload($portalNodeKey, $config),
            ]));
        };

        foreach ($this->configurationProcessors as $configurationProcessor) {
            $writeConfiguration = $write;
            $write = static function (array $config) use ($configurationProcessor, $portalNodeKey, $writeConfiguration): void {
                $configurationProcessor->write($portalNodeKey, $config, $writeConfiguration);
            };
        }

        $write($configuration ?? []);
    }
}
