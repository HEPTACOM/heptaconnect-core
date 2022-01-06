<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;

class HttpHandleContextFactory implements HttpHandleContextFactoryInterface
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

    public function createContext(PortalNodeKeyInterface $portalNodeKey): HttpHandleContextInterface
    {
        return new HttpHandleContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey)
        );
    }
}
