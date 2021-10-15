<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;

class ReceiveContextFactory implements ReceiveContextFactoryInterface
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private EntityStatusContract $entityStatus;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        EntityStatusContract $entityStatus
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->entityStatus = $entityStatus;
    }

    public function createContext(PortalNodeKeyInterface $portalNodeKey): ReceiveContextInterface
    {
        return new ReceiveContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey),
            $this->mappingService,
            $this->entityStatus
        );
    }
}
