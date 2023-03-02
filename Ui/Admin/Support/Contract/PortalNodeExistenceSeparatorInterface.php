<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract;

use Heptacom\HeptaConnect\Core\Ui\Admin\Support\PortalNodeExistenceSeparationResult;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

interface PortalNodeExistenceSeparatorInterface
{
    /**
     * Separates the given keys by performing an existence check on the keys.
     *
     * @throws UnsupportedStorageKeyException
     */
    public function separateKeys(PortalNodeKeyCollection $portalNodeKeys): PortalNodeExistenceSeparationResult;
}
