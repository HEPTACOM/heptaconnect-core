<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
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
     * Boolean value indicating whether the request is expected to be dumped.
     */
    public const REQUEST_ATTRIBUTE_DUMPS_EXPECTED = self::REQUEST_ATTRIBUTE_PREFIX . 'dumps_expected';

    public function handle(ServerRequestInterface $request, PortalNodeKeyInterface $portalNodeKey): ResponseInterface;
}
