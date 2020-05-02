<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

interface ConfigurationServiceInterface
{
    public function getPortalNodeConfiguration(string $portalNodeId): ?\ArrayAccess;
}
