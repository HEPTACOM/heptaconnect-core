<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;

class EmitContext implements EmitContextInterface
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

        if (\is_null($portalNodeId)) {
            return null;
        }

        return $this->configurationService->getPortalNodeConfiguration($portalNodeId);
    }

    public function getPortalNode(MappingInterface $mapping): ?PortalNodeInterface
    {
        $portalNodeId = $mapping->getPortalNodeId();

        if (\is_null($portalNodeId)) {
            return null;
        }

        return $this->portalNodeRegistry->getPortalNode($portalNodeId);
    }
}
