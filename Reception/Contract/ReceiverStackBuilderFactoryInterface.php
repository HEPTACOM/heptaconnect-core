<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiverStackBuilderFactoryInterface
{
    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): ReceiverStackBuilderInterface;
}
