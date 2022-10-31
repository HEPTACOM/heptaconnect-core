<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Core\File\Filesystem\Contract\StreamWrapperInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface PortalNodeFilesystemStreamWrapperFactoryInterface
{
    /**
     * Create a stream wrapper, that provides a storage for the given portal node.
     */
    public function create(PortalNodeKeyInterface $portalNodeKey): StreamWrapperInterface;
}
