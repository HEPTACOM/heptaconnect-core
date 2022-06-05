<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PackageQueryMatcherInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

final class PackageQueryMatcher implements PackageQueryMatcherInterface
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(StorageKeyGeneratorContract $storageKeyGenerator)
    {
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function matchPortalNodeKeys(string $query, PortalNodeKeyCollection $portalNodeKeys): PortalNodeKeyCollection
    {
        if (\class_exists($query) || \interface_exists($query)) {
            return new PortalNodeKeyCollection();
        }

        try {
            $storageKey = $this->storageKeyGenerator->deserialize($query);

            return new PortalNodeKeyCollection($portalNodeKeys->filter(
                static fn (PortalNodeKeyInterface $key): bool => $key->equals($storageKey)
            ));
        } catch (UnsupportedStorageKeyException $e) {
            return new PortalNodeKeyCollection($portalNodeKeys->filter(
                fn (PortalNodeKeyInterface $key): bool =>
                    $this->storageKeyGenerator->serialize($key->withAlias()) === $query ||
                    $this->storageKeyGenerator->serialize($key->withoutAlias()) === $query
            ));
        }
    }

    public function matchPortals(string $query, PortalCollection $portals): PortalCollection
    {
        if (!\class_exists($query) && !\interface_exists($query)) {
            return new PortalCollection();
        }

        return new PortalCollection($portals->filter(static fn (object $object): bool => $object instanceof $query));
    }

    public function matchPortalExtensions(string $query, PortalExtensionCollection $portalExtensions): PortalExtensionCollection
    {
        if (!\class_exists($query) && !\interface_exists($query)) {
            return new PortalExtensionCollection();
        }

        return new PortalExtensionCollection($portalExtensions->filter(
            static fn (object $object): bool => $object instanceof $query
        ));
    }
}
