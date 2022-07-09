<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class EmitterStackBuilderFactory implements EmitterStackBuilderFactoryInterface
{
    private PortalStackServiceContainerFactory $portalContainerFactory;

    private LoggerInterface $logger;

    public function __construct(PortalStackServiceContainerFactory $portalContainerFactory, LoggerInterface $logger)
    {
        $this->portalContainerFactory = $portalContainerFactory;
        $this->logger = $logger;
    }

    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityTypeClassString $entityType
    ): EmitterStackBuilderInterface {
        $flowComponentRegistry = $this->portalContainerFactory->create($portalNodeKey)->getFlowComponentRegistry();
        $components = new EmitterCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getEmitters($source));
        }

        return new EmitterStackBuilder($components, $entityType, $this->logger);
    }
}
