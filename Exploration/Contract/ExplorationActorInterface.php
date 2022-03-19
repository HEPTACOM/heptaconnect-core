<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

interface ExplorationActorInterface
{
    /**
     * @param class-string<DatasetEntityContract> $entityType
     */
    public function performExploration(
        string $entityType,
        ExplorerStackInterface $stack,
        ExploreContextInterface $context
    ): void;
}
