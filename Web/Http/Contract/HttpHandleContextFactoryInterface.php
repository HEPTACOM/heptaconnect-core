<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;

interface HttpHandleContextFactoryInterface
{
    /**
     * Create a context for handling an HTTP request on the given portal node.
     */
    public function createContext(PortalNodeKeyInterface $portalNodeKey): HttpHandleContextInterface;
}
