<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmitContextFactoryInterface
{
    /**
     * Create a context for an emission on the given portal node.
     * The direct emission flag is used in explorations, that trigger an emission as well.
     */
    public function createContext(PortalNodeKeyInterface $portalNodeKey, bool $directEmission = false): EmitContextInterface;
}
