<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Explore;

use Heptacom\HeptaConnect\Core\Explore\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Explore\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerInterface;
use Heptacom\HeptaConnect\Portal\Base\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreService implements ExploreServiceInterface
{
    private ExploreContextFactoryInterface $exploreContextFactory;

    private PortalNodeRegistryInterface $portalNodeRegistry;

    private PublisherInterface $publisher;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalNodeRegistryInterface $portalNodeRegistry,
        PublisherInterface $publisher
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalNodeRegistry = $portalNodeRegistry;
        $this->publisher = $publisher;
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey): void
    {
        $context = $this->exploreContextFactory->factory($portalNodeKey);

        $portalNode = $this->portalNodeRegistry->getPortalNode($portalNodeKey);
        $portalNodeExtensions = $this->portalNodeRegistry->getPortalNodeExtensions($portalNodeKey);

        $explorers = $portalNode->getExplorers();

        /** @var PortalNodeExtensionInterface $portalNodeExtension */
        foreach ($portalNodeExtensions as $portalNodeExtension) {
            $explorers->push($portalNodeExtension->getExplorerDecorators());
        }

        /** @var string $supportedType */
        foreach (self::getSupportedTypes($explorers) as $supportedType) {
            $explorerStack = new ExplorerStack($explorers->bySupport($supportedType));

            foreach ($explorerStack->next($context) as $entity) {
                if (!$entity instanceof DatasetEntityInterface) {
                    continue;
                }

                $this->publisher->publish(\get_class($entity), $portalNodeKey, $entity->getPrimaryKey());
            }
        }
    }

    protected static function getSupportedTypes(ExplorerCollection $explorers): array
    {
        $types = [];

        /** @var ExplorerInterface $explorer */
        foreach ($explorers as $explorer) {
            $types[$explorer->supports()] = true;
        }

        return \array_keys($types);
    }
}
