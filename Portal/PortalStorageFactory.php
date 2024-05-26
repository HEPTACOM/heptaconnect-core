<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Storage\Contract\PortalNodeStorageItemPackerInterface;
use Heptacom\HeptaConnect\Core\Portal\Storage\Contract\PortalNodeStorageItemUnpackerInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Psr\Log\LoggerInterface;

class PortalStorageFactory
{
    public function __construct(
        private readonly PortalNodeStorageItemPackerInterface $storageItemPacker,
        private readonly PortalNodeStorageItemUnpackerInterface $storageItemUnpacker,
        private readonly PortalNodeStorageClearActionInterface $storageClearAction,
        private readonly PortalNodeStorageDeleteActionInterface $storageDeleteAction,
        private readonly PortalNodeStorageGetActionInterface $storageGetAction,
        private readonly PortalNodeStorageListActionInterface $storageListAction,
        private readonly PortalNodeStorageSetActionInterface $storageSetAction,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return new PreviewPortalNodeStorage();
        }

        return new PortalStorage(
            $this->storageItemPacker,
            $this->storageItemUnpacker,
            $this->storageClearAction,
            $this->storageDeleteAction,
            $this->storageGetAction,
            $this->storageListAction,
            $this->storageSetAction,
            $this->logger,
            $portalNodeKey
        );
    }
}
