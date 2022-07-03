<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\Support\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiverStackBuilderFactoryInterface
{
    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityTypeClassString $entityType
    ): ReceiverStackBuilderInterface;
}
