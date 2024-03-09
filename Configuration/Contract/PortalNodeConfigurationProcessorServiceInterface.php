<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * Service, that applies all @see PortalNodeConfigurationProcessorInterface for either reading or writing.
 */
interface PortalNodeConfigurationProcessorServiceInterface
{
    /**
     * Apply all @see PortalNodeConfigurationProcessorInterface after reading it from storage.
     *
     * @param \Closure(): array $read
     */
    public function applyRead(PortalNodeKeyInterface $portalNodeKey, \Closure $read): array;

    /**
     * Apply all @see PortalNodeConfigurationProcessorInterface before writing it to storage.
     *
     * @param \Closure(array): void $write
     */
    public function applyWrite(PortalNodeKeyInterface $portalNodeKey, array $configuration, \Closure $write): void;
}
