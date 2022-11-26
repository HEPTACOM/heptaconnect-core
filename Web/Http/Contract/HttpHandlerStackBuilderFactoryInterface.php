<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpHandlerContract;

interface HttpHandlerStackBuilderFactoryInterface
{
    /**
     * Creates a stack builder, that is used to order @see HttpHandlerContract in the right order for the given scenario.
     */
    public function createHttpHandlerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $path
    ): HttpHandlerStackBuilderInterface;
}
