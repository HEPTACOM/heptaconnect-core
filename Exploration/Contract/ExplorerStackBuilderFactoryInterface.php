<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExplorerStackBuilderFactoryInterface
{
    /**
     * @param class-string<DatasetEntityContract> $entityType
     */
    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityType
    ): ExplorerStackBuilderInterface;
}
