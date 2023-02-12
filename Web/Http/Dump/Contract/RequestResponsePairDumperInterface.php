<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Dump\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RequestResponsePairDumperInterface
{
    /**
     * Dumps the given request and response.
     * The request and response are stored in a way, that they can be identified as belonging together.
     */
    public function dump(
        HttpHandlerStackIdentifier $httpHandler,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void;
}
