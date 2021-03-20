<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class EmitterStackBuilderFactory implements EmitterStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    public function __construct(PortalRegistryInterface $portalRegistry)
    {
        $this->portalRegistry = $portalRegistry;
    }

    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): EmitterStackBuilderInterface {
        return new EmitterStackBuilder($this->portalRegistry, $portalNodeKey, $entityClassName);
    }
}
