<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

interface ConfigurationServiceInterface
{
    /**
     * @psalm-return \ArrayAccess<array-key, mixed>|null
     */
    public function getPortalNodeConfiguration(string $portalNodeId): ?\ArrayAccess;
}
