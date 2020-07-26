<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ConfigurationServiceInterface
{
    /**
     * @psalm-return \ArrayAccess<array-key, mixed>|null
     */
    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?\ArrayAccess;
}
