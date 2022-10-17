<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

final class PackageQueryMatcher implements PackageQueryMatcherInterface
{
    public function __construct(private StorageKeyGeneratorContract $storageKeyGenerator)
    {
    }

    public function matchPortalNodeKeys(string $query, PortalNodeKeyCollection $portalNodeKeys): PortalNodeKeyCollection
    {
        if (\class_exists($query) || \interface_exists($query)) {
            return new PortalNodeKeyCollection();
        }

        try {
            $storageKey = $this->storageKeyGenerator->deserialize($query);

            if (!$storageKey instanceof PortalNodeKeyInterface) {
                return new PortalNodeKeyCollection();
            }

            if (!$portalNodeKeys->contains($storageKey)) {
                return new PortalNodeKeyCollection();
            }

            return new PortalNodeKeyCollection([$storageKey]);
        } catch (UnsupportedStorageKeyException) {
            return $portalNodeKeys->filter(
                fn (PortalNodeKeyInterface $key): bool => $this->storageKeyGenerator->serialize($key->withAlias()) === $query
                    || $this->storageKeyGenerator->serialize($key->withoutAlias()) === $query
            );
        }
    }

    public function matchPortals(string $query, PortalCollection $portals): PortalCollection
    {
        if (!\class_exists($query) && !\interface_exists($query)) {
            return new PortalCollection();
        }

        return $portals->filter(
            static fn (PortalContract $portal): bool => $portal instanceof $query
        );
    }

    public function matchPortalExtensions(string $query, PortalExtensionCollection $portalExtensions): PortalExtensionCollection
    {
        if (!\class_exists($query) && !\interface_exists($query)) {
            return new PortalExtensionCollection();
        }

        return $portalExtensions->filter(
            static fn (PortalExtensionContract $portalExtension): bool => $portalExtension instanceof $query
        );
    }
}
