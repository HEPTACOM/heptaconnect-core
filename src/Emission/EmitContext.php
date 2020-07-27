<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;

class EmitContext implements EmitContextInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalNodeRegistry;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalNodeRegistry
    ) {
        $this->configurationService = $configurationService;
        $this->portalNodeRegistry = $portalNodeRegistry;
    }

    public function getConfig(MappingInterface $mapping): ?\ArrayAccess
    {
        return $this->configurationService->getPortalNodeConfiguration($mapping->getPortalNodeKey());
    }

    public function getPortalNode(MappingInterface $mapping): ?PortalInterface
    {
        return $this->portalNodeRegistry->getPortal($mapping->getPortalNodeKey());
    }
}
