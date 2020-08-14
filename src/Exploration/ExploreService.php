<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreService implements ExploreServiceInterface
{
    private ExploreContextFactoryInterface $exploreContextFactory;

    private PortalRegistryInterface $portalRegistry;

    private PublisherInterface $publisher;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalRegistryInterface $portalRegistry,
        PublisherInterface $publisher
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalRegistry = $portalRegistry;
        $this->publisher = $publisher;
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey): void
    {
        $context = $this->exploreContextFactory->factory($portalNodeKey);
        $portal = $this->portalRegistry->getPortal($portalNodeKey);

        $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);

        $explorers = $portal->getExplorers();

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            $explorers->push($portalExtension->getExplorerDecorators());
        }

        foreach (self::getSupportedTypes($explorers) as $supportedType) {
            $explorerStack = new ExplorerStack($explorers->bySupport($supportedType));

            /** @var DatasetEntityInterface|null $entity */
            foreach ($explorerStack->next($context) as $entity) {
                if (!$entity instanceof DatasetEntityInterface) {
                    continue;
                }

                $this->publisher->publish(\get_class($entity), $portalNodeKey, $entity->getPrimaryKey());
            }
        }
    }

    /**
     * @psalm-return array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>>
     *
     * @return array|string[]
     */
    protected static function getSupportedTypes(ExplorerCollection $explorers): array
    {
        $types = [];

        /** @var ExplorerContract $explorer */
        foreach ($explorers as $explorer) {
            $types[$explorer->supports()] = true;
        }

        return \array_keys($types);
    }
}
