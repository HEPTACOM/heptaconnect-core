<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface HttpHandlerDumpDirectoryPathProviderInterface
{
    /**
     * Return a path to a directory, where the http handler dumps are stored.
     * The directory exists and is writable.
     * The returned path MUST contain a trailing slash.
     */
    public function provide(PortalNodeKeyInterface $portalNodeKey): string;
}
