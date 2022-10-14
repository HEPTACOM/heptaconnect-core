<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiveServiceInterface
{
    /**
     * Executes a reception for the given entities through a stack of @see ReceiverContract for the given portal node stack.
     * The given entities must already be reflected and therefore contain only keys that are already known to the portal node (excluding key histories).
     */
    public function receive(TypedDatasetEntityCollection $entities, PortalNodeKeyInterface $portalNodeKey): void;
}
