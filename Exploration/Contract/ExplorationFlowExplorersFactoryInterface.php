<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface ExplorationFlowExplorersFactoryInterface
{
    /**
     * Returns a list of explorers, that provide core functionality for the exploration flow.
     */
    public function createExplorers(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): ExplorerCollection;
}
