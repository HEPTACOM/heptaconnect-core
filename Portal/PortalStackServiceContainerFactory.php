<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Bridge\Portal\PortalContainerServiceProviderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalStackServiceContainerBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PortalStackServiceContainerFactory
{
    private PortalRegistryInterface $portalRegistry;

    private PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private PortalContainerServiceProviderInterface $portalContainerServiceProvider;

    private array $portalContainers = [];

    public function __construct(
        PortalRegistryInterface $portalRegistry,
        PortalStackServiceContainerBuilderInterface $portalStackServiceContainerBuilder,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalContainerServiceProviderInterface $portalContainerServiceProvider
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalStackServiceContainerBuilder = $portalStackServiceContainerBuilder;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalContainerServiceProvider = $portalContainerServiceProvider;
    }

    public function create(PortalNodeKeyInterface $portalNodeKey): ContainerInterface
    {
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);
        $result = $this->portalContainers[$key] ?? null;

        if ($result instanceof ContainerInterface) {
            return $result;
        }

        $result = $this->portalStackServiceContainerBuilder->build(
            $this->portalRegistry->getPortal($portalNodeKey),
            $this->portalRegistry->getPortalExtensions($portalNodeKey),
            $portalNodeKey
        );
        $this->setSyntheticServices($result, [
            PortalContainerServiceProviderInterface::class => $this->portalContainerServiceProvider,
        ]);
        $result->compile();
        $this->portalContainers[$key] = $result;

        return $result;
    }

    /**
     * @param object[] $services
     */
    private function setSyntheticServices(ContainerBuilder $containerBuilder, array $services): void
    {
        foreach ($services as $id => $service) {
            $definitionId = (string) $id;
            $containerBuilder->set($definitionId, $service);
            $definition = (new Definition())
                ->setSynthetic(true)
                ->setClass(\get_class($service));
            $containerBuilder->setDefinition($definitionId, $definition);
        }
    }
}
