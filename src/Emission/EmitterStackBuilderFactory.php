<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class EmitterStackBuilderFactory implements EmitterStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private LoggerInterface $logger;

    public function __construct(PortalRegistryInterface $portalRegistry, LoggerInterface $logger)
    {
        $this->portalRegistry = $portalRegistry;
        $this->logger = $logger;
    }

    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): EmitterStackBuilderInterface {
        return new EmitterStackBuilder($this->portalRegistry, $this->logger, $portalNodeKey, $entityClassName);
    }
}
