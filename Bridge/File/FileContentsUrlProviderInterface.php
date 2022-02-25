<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\UriInterface;

interface FileContentsUrlProviderInterface
{
    // TODO: Add token for one-time permission
    public function resolve(
        PortalNodeKeyInterface $portalNodeKey,
        string $normalizedStream,
        string $mimeType
    ): UriInterface;
}
