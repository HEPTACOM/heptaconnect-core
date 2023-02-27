<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Storage\Contract;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetItem;

interface PortalNodeStorageItemPackerInterface
{
    /**
     * Pack the given PHP value into a storage item.
     */
    public function pack(string $key, mixed $value, ?\DateInterval $ttl): ?PortalNodeStorageSetItem;
}
