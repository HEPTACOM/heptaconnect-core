<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\StatusReporting\Contract\StatusReportingContextFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Symfony\Component\DependencyInjection\Container;

class PortalStackServiceContainerFactory
{
    private PortalRegistryInterface $portalRegistry;

    private PortalStackServiceContainerBuilder $portalStackServiceContainerBuilder;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private StatusReportingContextFactoryInterface $statusReportingContextFactory;

    private array $portalContainers = [];

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        PortalStackServiceContainerBuilder $portalStackServiceContainerBuilder,
        StorageKeyGeneratorContract $storageKeyGenerator,
        StatusReportingContextFactoryInterface $statusReportingContextFactory
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalStackServiceContainerBuilder = $portalStackServiceContainerBuilder;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->statusReportingContextFactory = $statusReportingContextFactory;
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): Container
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);
        $result = $this->portalContainers[$key] ?? null;

        if ($result instanceof PortalStackServiceContainer) {
            return $result;
        }

        $context = $this->statusReportingContextFactory->factory($portalNodeKey);

        $result = $this->portalStackServiceContainerBuilder->build(
            $this->portalRegistry->getPortal($portalNodeKey),
            $this->portalRegistry->getPortalExtensions($portalNodeKey),
            $context
        );
        $this->portalContainers[$key] = $result;

        return $result;
    }
}
