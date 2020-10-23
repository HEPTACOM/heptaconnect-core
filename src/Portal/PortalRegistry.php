<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Portal\Contract\PortalFactoryContract;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\PortalNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidPortalNodeKeyException;

class PortalRegistry implements PortalRegistryInterface
{
    private PortalFactoryContract $portalFactory;

    private PortalNodeRepositoryContract $portalNodeRepository;

    private ComposerPortalLoader $portalLoader;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $cache = [
        'classes' => [],
        'portals' => [],
        'portalExtensions' => [],
    ];

    public function __construct(
        PortalFactoryContract $portalFactory,
        PortalNodeRepositoryContract $portalNodeRepository,
        ComposerPortalLoader $portalLoader,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->portalFactory = $portalFactory;
        $this->portalNodeRepository = $portalNodeRepository;
        $this->portalLoader = $portalLoader;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function getPortal(PortalNodeKeyInterface $portalNodeKey): PortalContract
    {
        $cacheKey = \md5($this->storageKeyGenerator->serialize($portalNodeKey));
        $portalClass = $this->cache['classes'][$cacheKey] ?? ($this->cache['classes'][$cacheKey] = $this->portalNodeRepository->read($portalNodeKey));

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
        $cacheKey = \md5($this->storageKeyGenerator->serialize($portalNodeKey));
        $portalClass = $this->cache['classes'][$cacheKey] ?? ($this->cache['classes'][$cacheKey] = $this->portalNodeRepository->read($portalNodeKey));

        if (!isset($this->cache['portalExtensions'][$portalClass])) {
            $extensions = $this->portalLoader->getPortalExtensions();

            $extensions = $extensions->filter(fn (PortalExtensionContract $extension) => \is_a($extension->supports(), $portalClass, true));

            $this->cache['portalExtensions'][$portalClass] = new PortalExtensionCollection(iterable_to_array($extensions));
        }

        return $this->cache['portalExtensions'][$portalClass];
    }
}
