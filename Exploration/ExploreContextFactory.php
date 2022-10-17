<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class ExploreContextFactory implements ExploreContextFactoryInterface
{
    public function __construct(private ConfigurationServiceInterface $configurationService, private PortalStackServiceContainerFactory $portalStackServiceContainerFactory)
    {
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): ExploreContextInterface
    {
        return new ExploreContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
        );
    }
}
