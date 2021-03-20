<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface EmitterStackBuilderFactoryInterface
{
    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityClassName
     */
    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): EmitterStackBuilderInterface;
}
