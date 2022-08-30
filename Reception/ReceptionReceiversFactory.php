<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionReceiversFactoryInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class ReceptionReceiversFactory implements ReceptionReceiversFactoryInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createReceivers(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): ReceiverCollection
    {
        return new ReceiverCollection([
            new LockingReceiver($entityType, $this->logger),
        ]);
    }
}
