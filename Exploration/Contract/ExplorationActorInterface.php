<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

interface ExplorationActorInterface
{
    /**
     * Perform an exploration for the given entity type on the given stack.
     */
    public function performExploration(
        EntityType $entityType,
        ExplorerStackInterface $stack,
        ExploreContextInterface $context
    ): void;
}
