<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Storage\Contract;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\PortalNodeStorageItemContract;

interface PortalNodeStorageItemUnpackerInterface
{
    /**
     * Unpack the given storage item into its PHP value.
     */
    public function unpack(PortalNodeStorageItemContract $storageItem): mixed;
}
