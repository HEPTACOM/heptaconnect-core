<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class ReceiverStackBuilderFactory implements ReceiverStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private LoggerInterface $logger;

    public function __construct(PortalRegistryInterface $portalRegistry, LoggerInterface $logger)
    {
        $this->portalRegistry = $portalRegistry;
        $this->logger = $logger;
    }

    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): ReceiverStackBuilderInterface {
        return new ReceiverStackBuilder($this->portalRegistry, $this->logger, $portalNodeKey, $entityClassName);
    }
}
