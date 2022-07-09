<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class ExplorerStackBuilderFactory implements ExplorerStackBuilderFactoryInterface
{
    private PortalStackServiceContainerFactory $portalContainerFactory;

    private LoggerInterface $logger;

    public function __construct(PortalStackServiceContainerFactory $portalContainerFactory, LoggerInterface $logger)
    {
        $this->portalContainerFactory = $portalContainerFactory;
        $this->logger = $logger;
    }

    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityTypeClassString $entityType
    ): ExplorerStackBuilderInterface {
        $flowComponentRegistry = $this->portalContainerFactory
            ->create($portalNodeKey)
            ->getFlowComponentRegistry();
        $components = new ExplorerCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getExplorers($source));
        }

        return new ExplorerStackBuilder($components, $entityType, $this->logger);
    }
}
