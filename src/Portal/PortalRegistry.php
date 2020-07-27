<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class PortalRegistry implements PortalRegistryInterface
{
    private PortalFactoryInterface $portalFactory;

    private StorageInterface $storage;

    private ComposerPortalLoader $portalLoader;

    public function __construct(
        PortalFactoryInterface $portalFactory,
        StorageInterface $storage,
        ComposerPortalLoader $portalLoader
    ) {
        $this->portalFactory = $portalFactory;
        $this->storage = $storage;
        $this->portalLoader = $portalLoader;
    }

    public function getPortal(PortalNodeKeyInterface $portalNodeKey): ?PortalInterface
    {
        $portalClass = $this->storage->getPortalNode($portalNodeKey);

        if (!\is_a($portalClass, PortalInterface::class, true)) {
            return null;
        }

        /* @phpstan-ignore-next-line $portalClass is class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface> */
        return $this->portalFactory->instantiatePortal($portalClass);
    }

    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection
    {
        $portalClass = $this->storage->getPortalNode($portalNodeKey);
        $extensions = $this->portalLoader->getPortalExtensions();

        $extensions = $extensions->filter(function (PortalExtensionInterface $extension) use ($portalClass): bool {
            return \is_a($extension->supports(), $portalClass, true);
        });

        return new PortalExtensionCollection($extensions);
    }
}
