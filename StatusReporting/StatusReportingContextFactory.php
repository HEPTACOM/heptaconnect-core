<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class StatusReportingContextFactory implements StatusReportingContextFactoryInterface
{
    public function __construct(
        private ConfigurationServiceInterface $configurationService,
        private PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
    }

    public function factory(PortalNodeKeyInterface $portalNodeKey): StatusReportingContextInterface
    {
        return new StatusReportingContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
        );
    }
}
