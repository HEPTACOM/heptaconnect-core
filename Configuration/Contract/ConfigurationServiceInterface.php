<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

/**
 * Read and write configuration for portal nodes.
 */
interface ConfigurationServiceInterface
{
    /**
     * Loads a portal node's configuration.
     */
    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?array;

    /**
     * Stores a portal node's configuration.
     */
    public function setPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $configuration): void;
}
