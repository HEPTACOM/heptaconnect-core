<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ReceiverStackBuilderFactory implements ReceiverStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    public function __construct(PortalRegistryInterface $portalRegistry)
    {
        $this->portalRegistry = $portalRegistry;
    }

    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): ReceiverStackBuilderInterface {
        return new ReceiverStackBuilder($this->portalRegistry, $portalNodeKey, $entityClassName);
    }
}
