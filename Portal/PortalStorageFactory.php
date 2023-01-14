<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Storage\PortalNodeStorageItemPacker;
use Heptacom\HeptaConnect\Core\Portal\Storage\PortalNodeStorageItemUnpacker;
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
        private PortalNodeStorageItemPacker $portalNodeStorageItemPacker,
        private PortalNodeStorageItemUnpacker $portalNodeStorageItemUnpacker,
        private PortalNodeStorageClearActionInterface $portalNodeStorageClearAction,
        private PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction,
        private PortalNodeStorageGetActionInterface $portalNodeStorageGetAction,
        private PortalNodeStorageListActionInterface $portalNodeStorageListAction,
        private PortalNodeStorageSetActionInterface $portalNodeStorageSetAction,
        private LoggerInterface $logger
    ) {
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return new PreviewPortalNodeStorage();
        }

        return new PortalStorage(
            $this->portalNodeStorageItemPacker,
            $this->portalNodeStorageItemUnpacker,
            $this->portalNodeStorageClearAction,
            $this->portalNodeStorageDeleteAction,
            $this->portalNodeStorageGetAction,
            $this->portalNodeStorageListAction,
            $this->portalNodeStorageSetAction,
            $this->logger,
            $portalNodeKey
        );
    }
}
