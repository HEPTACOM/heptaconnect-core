<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

interface ExplorationActorInterface
{
    public function performExploration(
        EntityTypeClassString $entityType,
        ExplorerStackInterface $stack,
        ExploreContextInterface $context
    ): void;
}
