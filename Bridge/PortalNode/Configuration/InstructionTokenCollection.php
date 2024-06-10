<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;
use Heptacom\HeptaConnect\Utility\Collection\AbstractObjectCollection;

/**
 * @extends AbstractObjectCollection<InstructionTokenContract>
 */
final class InstructionTokenCollection extends AbstractObjectCollection
{
    protected function getT(): string
    {
        return InstructionTokenContract::class;
    }
}
