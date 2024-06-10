<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\InstructionTokenCollection;

interface InstructionLoaderInterface
{
    /**
     * Load instruction tokes to alter portal node configurations.
     */
    public function loadInstructions(): InstructionTokenCollection;
}
