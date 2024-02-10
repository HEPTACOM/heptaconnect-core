<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class EmitterStackBuilderFactory implements EmitterStackBuilderFactoryInterface
{
    public function __construct(
        private PortalStackServiceContainerFactory $portalContainerFactory,
        private LoggerInterface $logger
    ) {
    }

    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): EmitterStackBuilderInterface {
        $flowComponentRegistry = $this->portalContainerFactory->create($portalNodeKey)->getFlowComponentRegistry();

        return new EmitterStackBuilder(
            $flowComponentRegistry->getEmitters(),
            $entityType,
            $this->logger
        );
    }
}
