<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExplorerStackBuilderFactoryInterface
{
    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityTypeClassString $entityType
    ): ExplorerStackBuilderInterface;
}
