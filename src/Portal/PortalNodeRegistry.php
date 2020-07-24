<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\PortalNodeExtensionCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class PortalNodeRegistry implements PortalNodeRegistryInterface
{
    private PortalFactoryInterface $portalFactory;

    private StorageInterface $storage;

    private ComposerPortalLoader $portalLoader;

    public function __construct(PortalFactoryInterface $portalFactory, StorageInterface $storage, ComposerPortalLoader $portalLoader)
    {
        $this->portalFactory = $portalFactory;
        $this->storage = $storage;
        $this->portalLoader = $portalLoader;
    }

    public function getPortalNode(PortalNodeKeyInterface $portalNodeKey): ?PortalNodeInterface
    {
        $portalClass = $this->storage->getPortalNode($portalNodeKey);

        return $this->portalFactory->instantiatePortalNode($portalClass);
    }

    public function getPortalNodeExtensions(PortalNodeKeyInterface $portalNodeKey): PortalNodeExtensionCollection
    {
        $portalClass = $this->storage->getPortalNode($portalNodeKey);
        $extensions = $this->portalLoader->getPortalExtensions();

        $extensions = $extensions->filter(function (PortalNodeExtensionInterface $extension) use ($portalClass): bool {
            return is_a($extension->supports(), $portalClass, true);
        });

        return new PortalNodeExtensionCollection($extensions);
    }
}
