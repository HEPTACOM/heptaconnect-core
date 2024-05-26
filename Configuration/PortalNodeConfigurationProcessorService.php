<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorInterface;
use Heptacom\HeptaConnect\Core\Configuration\Contract\PortalNodeConfigurationProcessorServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class PortalNodeConfigurationProcessorService implements PortalNodeConfigurationProcessorServiceInterface
{
    /**
     * @var PortalNodeConfigurationProcessorInterface[]
     */
    private readonly array $configProcessors;

    /**
     * @param iterable<PortalNodeConfigurationProcessorInterface> $configProcessors
     */
    public function __construct(iterable $configProcessors)
    {
        $this->configProcessors = \iterable_to_array($configProcessors);
    }

    #[\Override]
    public function applyRead(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array
    {
        foreach ($this->configProcessors as $configurationProcessor) {
            $readConfiguration = $read;
            $read = static fn (): array => $configurationProcessor->read($portalNodeKey, $readConfiguration);
        }

        return $read();
    }

    #[\Override]
    public function applyWrite(
        PortalNodeKeyInterface $portalNodeKey,
        array $configuration,
        \Closure $write
    ): void {
        foreach ($this->configProcessors as $configurationProcessor) {
            $writeConfiguration = $write;
            $write = static function (array $config) use ($configurationProcessor, $portalNodeKey, $writeConfiguration): void {
                $configurationProcessor->write($portalNodeKey, $config, $writeConfiguration);
            };
        }

        $write($configuration);
    }
}
