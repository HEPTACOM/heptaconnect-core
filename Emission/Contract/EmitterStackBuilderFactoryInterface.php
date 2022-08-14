<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmitterStackBuilderFactoryInterface
{
    /**
     * Creates a stack builder, that is used to order @see EmitterContract in the right order for the given scenario.
     */
    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        EntityType $entityType
    ): EmitterStackBuilderInterface;
}
