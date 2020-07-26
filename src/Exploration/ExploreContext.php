<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContext implements ExploreContextInterface
{
    private PortalNodeRegistryInterface $portalNodeRegistry;

    private ConfigurationServiceInterface $configurationService;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        PortalNodeRegistryInterface $portalNodeRegistry,
        ConfigurationServiceInterface $configurationService,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->portalNodeRegistry = $portalNodeRegistry;
        $this->configurationService = $configurationService;
        $this->portalNodeKey = $portalNodeKey;
    }

    public function getPortalNode(): PortalNodeInterface
    {
        return $this->portalNodeRegistry->getPortalNode($this->portalNodeKey);
    }

    public function getConfig(): ?\ArrayAccess
    {
        return $this->configurationService->getPortalNodeConfiguration($this->portalNodeKey);
    }
}
