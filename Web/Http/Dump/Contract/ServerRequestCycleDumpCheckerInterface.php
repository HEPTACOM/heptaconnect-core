<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\ServerRequestCycle;

interface ServerRequestCycleDumpCheckerInterface
{
    /**
     * Decide whether a request and its response shall be dumped.
     * It can inspect the request cycle and HTTP handler identifier.
     */
    public function shallDump(HttpHandlerStackIdentifier $httpHandler, ServerRequestCycle $requestCycle): bool;
}
