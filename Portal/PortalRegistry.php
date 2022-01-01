<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidPortalNodeKeyException;

class PortalRegistry implements PortalRegistryInterface
{
    private PortalFactoryContract $portalFactory;

    private ComposerPortalLoader $portalLoader;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private PortalNodeGetActionInterface $portalNodeGetAction;

    private array $cache = [
        'classes' => [],
        'portals' => [],
        'portalExtensions' => [],
    ];

    public function __construct(
        PortalFactoryContract $portalFactory,
        ComposerPortalLoader $portalLoader,
        StorageKeyGeneratorContract $storageKeyGenerator,
        PortalNodeGetActionInterface $portalNodeGetAction
    ) {
        $this->portalFactory = $portalFactory;
        $this->portalLoader = $portalLoader;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->portalNodeGetAction = $portalNodeGetAction;
    }

    public function getPortal(PortalNodeKeyInterface $portalNodeKey): PortalContract
    {
        $portalClass = $this->getPortalNodeClassCached($portalNodeKey);

        if (!isset($this->cache['portals'][$portalClass])) {
            if (!\is_a($portalClass, PortalContract::class, true)) {
                throw new InvalidPortalNodeKeyException($portalNodeKey);
            }

            /* @phpstan-ignore-next-line $portalClass is class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract> */
            $this->cache['portals'][$portalClass] = $this->portalFactory->instantiatePortal($portalClass);
        }

        return $this->cache['portals'][$portalClass];
    }

    public function getPortalExtensions(PortalNodeKeyInterface $portalNodeKey): PortalExtensionCollection
    {
        $portalClass = $this->getPortalNodeClassCached($portalNodeKey);

        if (!isset($this->cache['portalExtensions'][$portalClass])) {
            $extensions = $this->portalLoader->getPortalExtensions();

            $extensions = $extensions->filter(fn (PortalExtensionContract $extension) => \is_a($extension->supports(), $portalClass, true));

            $this->cache['portalExtensions'][$portalClass] = new PortalExtensionCollection(\iterable_to_array($extensions));
        }

        return $this->cache['portalExtensions'][$portalClass];
    }

    private function getPortalNodeClassCached(PortalNodeKeyInterface $portalNodeKey): ?string
    {
        $cacheKey = \md5($this->storageKeyGenerator->serialize($portalNodeKey));

        return $this->cache['classes'][$cacheKey] ?? ($this->cache['classes'][$cacheKey] = $this->getPortalNodeClass($portalNodeKey));
    }

    private function getPortalNodeClass(PortalNodeKeyInterface $portalNodeKey): ?string
    {
        $nodes = $this->portalNodeGetAction->get(new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey])));

        foreach ($nodes as $portalNode) {
            return $portalNode->getPortalClass();
        }

        return null;
    }
}
