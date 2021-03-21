<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExplorerStackBuilderFactory implements ExplorerStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    public function __construct(PortalRegistryInterface $portalRegistry)
    {
        $this->portalRegistry = $portalRegistry;
    }

    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): ExplorerStackBuilderInterface {
        return new ExplorerStackBuilder($this->portalRegistry, $portalNodeKey, $entityClassName);
    }
}
