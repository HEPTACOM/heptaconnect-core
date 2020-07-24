<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;

class ConfigurationService implements ConfigurationServiceInterface
{
    public function getPortalNodeConfiguration(PortalNodeKeyInterface $portalNodeKey): ?\ArrayAccess
    {
        return new \ArrayObject();
    }
}
