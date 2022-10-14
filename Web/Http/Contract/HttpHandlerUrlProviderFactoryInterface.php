<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerUrlProviderInterface;

interface HttpHandlerUrlProviderFactoryInterface
{
    /**
     * Creates a URL provider for @see HttpHandlerContract to generate URLs, that can later be routed back again to @see \Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandleServiceInterface
     */
    public function factory(PortalNodeKeyInterface $portalNodeKey): HttpHandlerUrlProviderInterface;
}
