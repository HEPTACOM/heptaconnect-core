<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\PortalCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;

/**
 * Central service for query matching that is mostly used in human or configuration interaction.
 */
interface PackageQueryMatcherInterface
{
    /**
     * Tests all given portal node keys against the given query and returns every matching portal node key.
     * Query by portal node alias and storage key must be implemented.
     */
    public function matchPortalNodeKeys(string $query, PortalNodeKeyCollection $portalNodeKeys): PortalNodeKeyCollection;

    /**
     * Tests all given portals against the given query and returns every matching portal.
     * Query by class-string must be implemented.
     *
     * @param class-string|string $query
     */
    public function matchPortals(string $query, PortalCollection $portals): PortalCollection;

    /**
     * Tests all given portal extensions against the given query and returns every matching portal extension.
     * Query by class-string must be implemented.
     *
     * @param class-string $query
     */
    public function matchPortalExtensions(string $query, PortalExtensionCollection $portalExtensions): PortalExtensionCollection;
}
