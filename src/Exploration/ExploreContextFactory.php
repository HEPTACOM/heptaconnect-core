<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContextFactory implements ExploreContextFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationServiceInterface $configurationService;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationServiceInterface $configurationService
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->configurationService = $configurationService;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface
    {
        return new ExploreContext($this->portalRegistry, $this->configurationService, $portalNodeKey);
    }
}