<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContextFactory implements ExploreContextFactoryInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->configurationService = $configurationService;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface
    {
        return new ExploreContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
        );
    }
}
