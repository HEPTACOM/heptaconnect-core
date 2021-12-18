<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerUrlProviderInterface;

interface HttpHandlerUrlProviderFactoryInterface
{
    public function factory(PortalNodeKeyInterface $portalNodeKey): HttpHandlerUrlProviderInterface;
}
