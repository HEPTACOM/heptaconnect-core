<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\StoragePortalNodeKeyInterface;

interface ConfigurationServiceInterface
{
    /**
     * @psalm-return \ArrayAccess<array-key, mixed>|null
     */
    public function getPortalNodeConfiguration(StoragePortalNodeKeyInterface $portalNodeKey): ?\ArrayAccess;
}
