<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\Portal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientUtilityFactoryInterface;
use Psr\Log\LoggerInterface;

interface PortalContainerServiceProviderInterface
{
    public function createHttpClientUtilityFactory(
        PortalNodeKeyInterface $portalNodeKey,
        LoggerInterface $logger
    ): HttpClientUtilityFactoryInterface;
}
