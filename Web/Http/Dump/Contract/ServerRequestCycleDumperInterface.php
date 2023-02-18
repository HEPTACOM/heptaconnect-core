<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\ServerRequestCycle;

interface ServerRequestCycleDumperInterface
{
    /**
     * Dumps the given request cycle.
     * The request cycle are stored in a way, that they can be identified as belonging together.
     */
    public function dump(HttpHandlerStackIdentifier $httpHandler, ServerRequestCycle $requestCycle): void;
}
