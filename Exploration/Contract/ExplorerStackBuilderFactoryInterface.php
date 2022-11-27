<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExplorerStackBuilderFactoryInterface
{
    /**
     * Creates a stack builder, that is used to order @see ExplorerContract in the right order for the given scenario.
     */
    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): ExplorerStackBuilderInterface;
}
