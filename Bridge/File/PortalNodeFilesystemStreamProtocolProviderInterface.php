<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\File;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface PortalNodeFilesystemStreamProtocolProviderInterface
{
    /**
     * Register a stream wrapper, that provides a storage for the given portal node, and returns its protocol.
     */
    public function provide(PortalNodeKeyInterface $portalNodeKey): string;
}
