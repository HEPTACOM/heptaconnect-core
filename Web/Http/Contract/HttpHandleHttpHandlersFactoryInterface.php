<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;

interface HttpHandleHttpHandlersFactoryInterface
{
    /**
     * Returns a list of HTTP handlers, that provide core functionality for the HTTP handle flow.
     */
    public function createHttpHandlers(PortalNodeKeyInterface $portalNodeKey, string $path): HttpHandlerCollection;
}
