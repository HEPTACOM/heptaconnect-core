<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration\Contract;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;

interface ExplorerStackProcessorInterface
{
    /**
     * Iterates over the result of the explorers of the stack.
     *
     * @return iterable<array-key, DatasetEntityContract|string>
     */
    public function processStack(ExplorerStackInterface $stack, ExploreContextInterface $context): iterable;
}
