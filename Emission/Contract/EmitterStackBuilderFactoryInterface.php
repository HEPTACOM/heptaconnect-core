<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Dataset\Base\Support\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmitterStackBuilderFactoryInterface
{
    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityTypeClassString $entityType
    ): EmitterStackBuilderInterface;
}
