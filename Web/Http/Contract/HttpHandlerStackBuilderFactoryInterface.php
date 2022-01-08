<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface HttpHandlerStackBuilderFactoryInterface
{
    public function createHttpHandlerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $path
    ): HttpHandlerStackBuilderInterface;
}
