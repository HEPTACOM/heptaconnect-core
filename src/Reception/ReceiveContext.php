<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;

class ReceiveContext implements ReceiveContextInterface
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockFacade $resourceLockFacade;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLockFacade = new ResourceLockFacade($resourceLocking);
    }

    public function getConfig(MappingInterface $mapping): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($mapping->getPortalNodeKey());
    }

    public function getPortal(MappingInterface $mapping): PortalContract
    {
        return $this->portalRegistry->getPortal($mapping->getPortalNodeKey());
    }

    public function markAsFailed(MappingInterface $mapping, \Throwable $throwable): void
    {
        $this->mappingService->addException($mapping, $throwable);
    }

    public function getStorage(MappingInterface $mapping): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($mapping->getPortalNodeKey());
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->resourceLockFacade;
    }
}
