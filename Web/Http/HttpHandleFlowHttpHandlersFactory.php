<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleFlowHttpHandlersFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Handler\HttpMiddlewareChainHandler;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;

final class HttpHandleFlowHttpHandlersFactory implements HttpHandleFlowHttpHandlersFactoryInterface
{
    public function createHttpHandlers(HttpHandlerStackIdentifier $stackIdentifier): HttpHandlerCollection
    {
        return new HttpHandlerCollection([
            new HttpMiddlewareChainHandler($stackIdentifier->getPath(), false),
        ]);
    }
}
