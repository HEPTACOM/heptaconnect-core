<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeContainerFacadeContract;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Container\ContainerInterface;

class PortalStackServiceContainerFactory
{
    private PortalRegistryInterface $portalRegistry;

    private PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    /**
     * @var PortalNodeContainerFacadeContract[]
     */
    private array $portalContainers = [];

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalStackServiceContainerBuilder = $portalStackServiceContainerBuilder;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): PortalNodeContainerFacadeContract
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);
        $result = $this->portalContainers[$key] ?? null;

        if ($result instanceof PortalNodeContainerFacadeContract) {
            return $result;
        }

        $container = $this->portalStackServiceContainerBuilder->build(
            $this->portalRegistry->getPortal($portalNodeKey),
            $this->portalRegistry->getPortalExtensions($portalNodeKey),
            $portalNodeKey
        );
        $container->compile();
        $result = new PortalNodeContainerFacade($container);
        $this->portalContainers[$key] = result;

        return $result;
    }
}
