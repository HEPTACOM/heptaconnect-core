<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\File\Filesystem\Contract;

use Heptacom\HeptaConnect\Portal\Base\File\Filesystem\Contract\FilesystemInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface FilesystemFactoryInterface
{
    /**
     * Creates a new filesystem interface, that is focused on a portal node.
     */
    public function create(PortalNodeKeyInterface $portalNodeKey): FilesystemInterface;
}
