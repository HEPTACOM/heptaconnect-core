<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiverStackBuilderFactoryInterface
{
    /**
     * Creates a stack builder, that is used to order @see ReceiverContract in the right order for the given scenario.
     */
    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): ReceiverStackBuilderInterface;
}
