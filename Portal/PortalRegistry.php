<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;

final class PortalRegistry implements PortalRegistryInterface
{
    private PortalFactoryContract $portalFactory;

    private ComposerPortalLoader $portalLoader;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private PortalNodeGetActionInterface $portalNodeGetAction;

    private PortalExtensionFindActionInterface $portalExtensionFindAction;

    /**
     * @var array{
     *             classes: array<string, PortalType|null>,
     *             portals: array<string, PortalContract>,
     *             portalExtensions: array
     *             }
     */
    private array $cache = [
        'classes' => [],
        'portals' => [],
        'portalExtensions' => [],
    ];

    public function __construct(
        PortalFactoryContract $portalFactory,
        ComposerPortalLoader $portalLoader,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalNodeGetActionInterface $portalNodeGetAction,
        PortalExtensionFindActionInterface $portalExtensionFindAction
    ) {
        $this->portalFactory = $portalFactory;
        $this->portalLoader = $portalLoader;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalNodeGetAction = $portalNodeGetAction;
        $this->portalExtensionFindAction = $portalExtensionFindAction;
    }

    public function getPortal(PortalNodeKeyInterface $portalNodeKey): PortalContract
    {
        $portalClass = $this->getPortalNodeClassCached($portalNodeKey);
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());

        if ($portalClass !== null && !isset($this->cache['portals'][$cacheKey])) {
            $this->cache['portals'][$cacheKey] = $this->portalFactory->instantiatePortal($portalClass);
        }

        return $this->cache['portals'][$cacheKey];
    }

    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection
    {
        $portalClass = $this->getPortalNodeClassCached($portalNodeKey);
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias());

        if ($portalClass !== null && !isset($this->cache['portalExtensions'][$cacheKey])) {
            $extensions = $this->portalLoader->getPortalExtensions()->bySupport($portalClass);

            if (!$extensions->isEmpty() && !$portalNodeKey instanceof PreviewPortalNodeKey) {
                $portalExtensionFindResult = $this->portalExtensionFindAction->find($portalNodeKey);

                $extensions = new PortalExtensionCollection($extensions->filter([$portalExtensionFindResult, 'isActive']));
            }

            $this->cache['portalExtensions'][$cacheKey] = $extensions;
        }

        return $this->cache['portalExtensions'][$cacheKey];
    }

    private function getPortalNodeClassCached(PortalNodeKeyInterface $portalNodeKey): ?PortalType
    {
        $cacheKey = \md5($this->storageKeyGenerator->serialize($portalNodeKey->withoutAlias()));

        return $this->cache['classes'][$cacheKey] ?? ($this->cache['classes'][$cacheKey] = $this->getPortalNodeClass($portalNodeKey));
    }

    private function getPortalNodeClass(PortalNodeKeyInterface $portalNodeKey): ?PortalType
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return $portalNodeKey->getPortalType();
        }

        $nodes = $this->portalNodeGetAction->get(new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey])));

        foreach ($nodes as $portalNode) {
            return new PortalType((string) $portalNode->getPortalClass());
        }

        return null;
    }
}
