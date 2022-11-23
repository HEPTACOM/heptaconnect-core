<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmitContextFactoryInterface
{
    public function createContext(PortalNodeKeyInterface $portalNodeKey, bool $directEmission = false): EmitContextInterface;
}
