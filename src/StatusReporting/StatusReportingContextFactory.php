<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class StatusReportingContextFactory implements StatusReportingContextFactoryInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalStackServiceContainerFactory $containerFactory;

    public function __construct(ConfigurationServiceInterface $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function setContainerFactory(PortalStackServiceContainerFactory $containerFactory): void
    {
        $this->containerFactory = $containerFactory;
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): StatusReportingContextInterface
    {
        return new StatusReportingContext(
            $this->containerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
        );
    }
}
