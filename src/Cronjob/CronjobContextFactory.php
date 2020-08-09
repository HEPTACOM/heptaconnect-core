<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Cronjob;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;

class CronjobContextFactory
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
    }

    public function createContext(CronjobInterface $cronjob): CronjobContextInterface
    {
        return new CronjobContext($this->configurationService, $this->portalRegistry, $cronjob);
    }
}
