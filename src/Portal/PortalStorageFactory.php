<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class PortalStorageFactory
{
    private NormalizationRegistry $normalizationRegistry;

    private StorageInterface $storage;

    public function __construct(NormalizationRegistry $normalizationRegistry, StorageInterface $storage)
    {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->storage = $storage;
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        return new PortalStorage($this->normalizationRegistry, $this->storage, $portalNodeKey);
    }
}
