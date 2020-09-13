<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;

class PortalStorageFactory
{
    private NormalizationRegistry $normalizationRegistry;

    private PortalStorageContract $portalStorage;

    public function __construct(NormalizationRegistry $normalizationRegistry, PortalStorageContract $portalStorage)
    {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalStorage = $portalStorage;
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        return new PortalStorage($this->normalizationRegistry, $this->portalStorage, $portalNodeKey);
    }
}
