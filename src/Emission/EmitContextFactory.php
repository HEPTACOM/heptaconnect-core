<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;

class EmitContextFactory implements EmitContextFactoryInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private MappingServiceInterface $mappingService;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        MappingServiceInterface $mappingService,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->configurationService = $configurationService;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->mappingService = $mappingService;
        $this->mappingNodeRepository = $mappingNodeRepository;
    }

    public function createContext(PortalNodeKeyInterface $portalNodeKey, bool $directEmission = false): EmitContextInterface
    {
        return new EmitContext(
            $this->portalStackServiceContainerFactory->create($portalNodeKey),
            $this->configurationService->getPortalNodeConfiguration($portalNodeKey),
            $this->mappingService,
            $this->mappingNodeRepository,
            $directEmission
        );
    }
}
