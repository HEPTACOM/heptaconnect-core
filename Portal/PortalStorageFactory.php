<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
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
    private NormalizationRegistryContract $normalizationRegistry;

    private PortalNodeStorageClearActionInterface $portalNodeStorageClearAction;

    private PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction;

    private PortalNodeStorageGetActionInterface $portalNodeStorageGetAction;

    private PortalNodeStorageListActionInterface $portalNodeStorageListAction;

    private PortalNodeStorageSetActionInterface $portalNodeStorageSetAction;

    private LoggerInterface $logger;

    public function __construct(
        NormalizationRegistryContract $normalizationRegistry,
        PortalNodeStorageClearActionInterface $portalNodeStorageClearAction,
        PortalNodeStorageDeleteActionInterface $portalNodeStorageDeleteAction,
        PortalNodeStorageGetActionInterface $portalNodeStorageGetAction,
        PortalNodeStorageListActionInterface $portalNodeStorageListAction,
        PortalNodeStorageSetActionInterface $portalNodeStorageSetAction,
        LoggerInterface $logger
    ) {
        $this->normalizationRegistry = $normalizationRegistry;
        $this->portalNodeStorageClearAction = $portalNodeStorageClearAction;
        $this->portalNodeStorageDeleteAction = $portalNodeStorageDeleteAction;
        $this->portalNodeStorageGetAction = $portalNodeStorageGetAction;
        $this->portalNodeStorageListAction = $portalNodeStorageListAction;
        $this->portalNodeStorageSetAction = $portalNodeStorageSetAction;
        $this->logger = $logger;
    }

    public function createPortalStorage(PortalNodeKeyInterface $portalNodeKey): PortalStorageInterface
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return new PreviewPortalNodeStorage();
        }

        return new PortalStorage(
            $this->normalizationRegistry,
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
