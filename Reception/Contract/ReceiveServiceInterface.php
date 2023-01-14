<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ReceiveServiceInterface
{
    public function receive(TypedDatasetEntityCollection $entities, PortalNodeKeyInterface $portalNodeKey): void;
}
