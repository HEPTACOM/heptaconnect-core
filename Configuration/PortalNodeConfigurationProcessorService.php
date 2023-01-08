<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
final class PortalNodeConfigurationProcessorService implements PortalNodeConfigurationProcessorServiceInterface
{
    /**
     * @var PortalNodeConfigurationProcessorInterface[]
     */
    private array $configurationProcessors;

    /**
     * @param iterable<PortalNodeConfigurationProcessorInterface> $configurationProcessors
     */
    public function __construct(iterable $configurationProcessors)
    {
        $this->configurationProcessors = \iterable_to_array($configurationProcessors);
    }

    public function applyRead(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array
    {
        foreach ($this->configurationProcessors as $configurationProcessor) {
            $readConfiguration = $read;
            $read = static fn (): array => $configurationProcessor->read($portalNodeKey, $readConfiguration);
        }

        return $read();
    }

    public function applyWrite(
        PortalNodeKeyInterface $portalNodeKey,
        array $configuration,
        \Closure $write
    ): void {
        foreach ($this->configurationProcessors as $configurationProcessor) {
            $writeConfiguration = $write;
            $write = static function (array $config) use ($configurationProcessor, $portalNodeKey, $writeConfiguration): void {
                $configurationProcessor->write($portalNodeKey, $config, $writeConfiguration);
            };
        }

        $write($configuration);
    }
}
