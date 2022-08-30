<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceptionReceiversFactoryInterface
{
    /**
     * Returns a list of receivers, that provide core functionality for the reception flow.
     */
    public function createReceivers(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): ReceiverCollection;
}
