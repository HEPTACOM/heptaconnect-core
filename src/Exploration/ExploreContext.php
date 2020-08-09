<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreContext implements ExploreContextInterface
{
    private PortalRegistryInterface $portalRegistry;

    private ConfigurationServiceInterface $configurationService;

    private PortalStorageFactory $portalStorageFactory;

    private PortalNodeKeyInterface $portalNodeKey;

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        ConfigurationServiceInterface $configurationService,
        PortalStorageFactory $portalStorageFactory,
        PortalNodeKeyInterface $portalNodeKey
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->configurationService = $configurationService;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->portalNodeKey = $portalNodeKey;
    }

    public function getPortal(): ?PortalContract
    {
        return $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    public function getConfig(): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($this->portalNodeKey);
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($this->portalNodeKey);
    }
}
