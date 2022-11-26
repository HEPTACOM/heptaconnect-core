<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HttpHandleServiceInterface
{
    /**
     * Builds a response for the given request by processing it by any matching @see HttpHandlerContract in the portal node stack.
     */
    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface;
}
