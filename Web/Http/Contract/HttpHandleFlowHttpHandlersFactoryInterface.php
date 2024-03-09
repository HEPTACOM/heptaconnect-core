<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;

interface HttpHandleFlowHttpHandlersFactoryInterface
{
    /**
     * Returns a list of HTTP handlers, that provide core functionality for the HTTP handle flow.
     */
    public function createHttpHandlers(HttpHandlerStackIdentifier $stackIdentifier): HttpHandlerCollection;
}
