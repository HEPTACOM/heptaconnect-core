<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiveContextFactoryInterface
{
    /**
     * Create a context for a reception on the given portal node.
     */
    public function createContext(PortalNodeKeyInterface $portalNodeKey): ReceiveContextInterface;
}
