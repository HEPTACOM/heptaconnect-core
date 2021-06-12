<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;

class CronjobContextFactory
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

    public function createContext(CronjobInterface $cronjob): CronjobContextInterface
    {
        return new CronjobContext(
            $this->portalStackServiceContainerFactory->create($cronjob->getPortalNodeKey()),
            $this->configurationService->getPortalNodeConfiguration($cronjob->getPortalNodeKey()),
            $cronjob
        );
    }
}
