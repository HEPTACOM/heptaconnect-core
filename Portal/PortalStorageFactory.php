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
        private readonly PortalNodeStorageItemPackerInterface $portalNodeStorageItemPacker,
        private readonly PortalNodeStorageItemUnpackerInterface $portalNodeStorageItemUnpacker,
        private readonly PortalNodeStorageClearActionInterface $portalNodeStorageClearAction,
        private readonly PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction,
        private readonly PortalNodeStorageGetActionInterface $portalNodeStorageGetAction,
        private readonly PortalNodeStorageListActionInterface $portalNodeStorageListAction,
        private readonly PortalNodeStorageSetActionInterface $portalNodeStorageSetAction,
        private readonly LoggerInterface $logger
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
