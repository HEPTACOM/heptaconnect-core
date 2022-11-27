<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleFlowHttpHandlersFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Handler\HttpMiddlewareChainHandler;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;

final class HttpHandleFlowHttpHandlersFactory implements HttpHandleFlowHttpHandlersFactoryInterface
{
    public function createHttpHandlers(PortalNodeKeyInterface $portalNodeKey, string $path): HttpHandlerCollection
    {
        return new HttpHandlerCollection([
            new HttpMiddlewareChainHandler($path),
        ]);
    }
}
