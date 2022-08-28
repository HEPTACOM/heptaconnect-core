<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmissionEmittersFactoryInterface
{
    /**
     * Returns a list of emitters, that provide core functionality for the emission flow.
     */
    public function createEmitters(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): EmitterCollection;
}
