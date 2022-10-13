<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandleContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerStackInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HttpHandlingActorInterface
{
    public function performHttpHandling(
        ServerRequestInterface $request,
        ResponseInterface $response,
        HttpHandlerStackInterface $stack,
        HttpHandleContextInterface $context
    ): ResponseInterface;
}
