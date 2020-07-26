<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Explore;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Explore\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContextFactory implements ExploreContextFactoryInterface
{
    private PortalNodeRegistryInterface $portalNodeRegistry;

    private ConfigurationServiceInterface $configurationService;

    public function __construct(
        PortalNodeRegistryInterface $portalNodeRegistry,
        ConfigurationServiceInterface $configurationService
    ) {
        $this->portalNodeRegistry = $portalNodeRegistry;
        $this->configurationService = $configurationService;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface
    {
        return new ExploreContext($this->portalNodeRegistry, $this->configurationService, $portalNodeKey);
    }
}
