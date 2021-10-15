<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Psr\Log\LoggerInterface;

class PortalStorageFactory
{
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    private LoggerInterface $logger;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalStorageContract $portalStorage,
        LoggerInterface $logger
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
        $this->logger = $logger;
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        return new PortalStorage($this->normalizationRegistry, $this->portalStorage, $this->logger, $portalNodeKey);
    }
}
