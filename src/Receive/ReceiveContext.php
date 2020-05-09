<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;

class ReceiveContext implements ReceiveContextInterface
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalNodeRegistryInterface $portalNodeRegistry;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalNodeRegistryInterface $portalNodeRegistry
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalNodeRegistry = $portalNodeRegistry;
    }

    public function getConfig(MappingInterface $mapping): ?\ArrayAccess
    {
        $portalNodeId = $mapping->getPortalNodeId();

        return $this->configurationService->getPortalNodeConfiguration($portalNodeId);
    }

    public function getPortalNode(MappingInterface $mapping): ?PortalNodeInterface
    {
        $portalNodeId = $mapping->getPortalNodeId();

        return $this->portalNodeRegistry->getPortalNode($portalNodeId);
    }

    public function markAsFailed(MappingInterface $mapping, \Throwable $throwable): void
    {
        $this->mappingService->addException($mapping, $throwable);
    }
}
