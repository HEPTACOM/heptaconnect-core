<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract;

interface InstructionLoaderInterface
{
    /**
     * Load instruction tokes to alter portal node configurations.
     *
     * @return InstructionTokenContract[]
     */
    public function loadInstructions(): array;
}
