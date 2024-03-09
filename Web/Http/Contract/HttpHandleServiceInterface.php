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
     * Attributes with this prefix will be removed before passing the request to a portal
     * as they are only expected to transfer data from a bridge to the core.
     */
    public const REQUEST_ATTRIBUTE_PREFIX = '@heptaconnect_core.';

    /**
     * An instance of @see ServerRequestInterface is expected as value and MUST NOT influence the handling and SHOULD only be used for debugging.
     */
    public const REQUEST_ATTRIBUTE_ORIGINAL_REQUEST = self::REQUEST_ATTRIBUTE_PREFIX . 'original_request';

    /**
     * Builds a response for the given request by processing it by any matching @see HttpHandlerContract in the portal node stack.
     */
    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface;
}
