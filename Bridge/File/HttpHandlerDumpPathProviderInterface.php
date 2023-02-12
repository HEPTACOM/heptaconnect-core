<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface HttpHandlerDumpPathProviderInterface
{
    /**
     * Return a path to a directory, where the http handler dumps are stored.
     * Any directory in the returned path exists.
     * The returned path MUST contain a trailing slash, if it points to a directory.
     */
    public function provide(PortalNodeKeyInterface $portalNodeKey): string;
}
