<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Message\UriInterface;

interface FileRequestUrlProviderInterface
{
    // TODO: Add token for one-time permission
    public function resolve(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $requestKey
    ): UriInterface;
}
