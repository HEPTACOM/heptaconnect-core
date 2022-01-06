<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\Portal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;

interface PortalContainerServiceProviderInterface
{
    public function createHttpClient(
        PortalNodeKeyInterface $portalNodeKey,
        ClientInterface $client,
        LoggerInterface $logger
    ): HttpClientInterface;
}
