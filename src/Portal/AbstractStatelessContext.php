<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Contract\ResourceLockingContract;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\StatelessContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractStatelessContext implements StatelessContextInterface
{
    private MappingServiceInterface $mappingService;

    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private ResourceLockFacade $resourceLockFacade;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        MappingServiceInterface $mappingService,
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        ResourceLockingContract $resourceLocking,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->mappingService = $mappingService;
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->resourceLockFacade = new ResourceLockFacade($resourceLocking);
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function getConfig(MappingInterface $mapping): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($mapping->getPortalNodeKey());
    }

    public function getResourceLocker(): ResourceLockFacade
    {
        return $this->resourceLockFacade;
    }

    public function getStorage(MappingInterface $mapping): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($mapping->getPortalNodeKey());
    }

    public function getPortalNodeKey(MappingInterface $mapping): PortalNodeKeyInterface
    {
        return $mapping->getPortalNodeKey();
    }

    public function getPortal(MappingInterface $mapping): PortalContract
    {
        return $this->portalRegistry->getPortal($mapping->getPortalNodeKey());
    }

    public function getContainer(MappingInterface $mapping): ContainerInterface
    {
        return $this->portalStackServiceContainerFactory->create($mapping->getPortalNodeKey());
    }

    public function markAsFailed(MappingInterface $mapping, \Throwable $throwable): void
    {
        $this->mappingService->addException($mapping, $throwable);
    }
}
