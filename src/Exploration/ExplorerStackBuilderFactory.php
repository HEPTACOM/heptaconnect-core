<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class ExplorerStackBuilderFactory implements ExplorerStackBuilderFactoryInterface
{
    private PortalRegistryInterface $portalRegistry;

    private LoggerInterface $logger;

    public function __construct(PortalRegistryInterface $portalRegistry, LoggerInterface $logger)
    {
        $this->portalRegistry = $portalRegistry;
        $this->logger = $logger;
    }

    public function createExplorerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): ExplorerStackBuilderInterface {
        return new ExplorerStackBuilder($this->portalRegistry, $this->logger, $portalNodeKey, $entityClassName);
    }
}
