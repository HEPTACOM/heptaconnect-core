<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;

class ReceiveContext implements ReceiveContextInterface
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
    }

    public function getConfig(MappingInterface $mapping): ?\ArrayAccess
    {
        return $this->configurationService->getPortalNodeConfiguration($mapping->getPortalNodeKey());
    }

    public function getPortal(MappingInterface $mapping): ?PortalContract
    {
        return $this->portalRegistry->getPortal($mapping->getPortalNodeKey());
    }

    public function markAsFailed(MappingInterface $mapping, \Throwable $throwable): void
    {
        $this->mappingService->addException($mapping, $throwable);
    }
}
