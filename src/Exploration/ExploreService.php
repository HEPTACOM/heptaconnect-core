<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ExploreService implements ExploreServiceInterface
{
    public const CHUNK_SIZE = 50;

    private ExploreContextFactoryInterface $exploreContextFactory;

    private PortalRegistryInterface $portalRegistry;

    private PublisherInterface $publisher;

    private MappingServiceInterface $mappingService;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalRegistryInterface $portalRegistry,
        PublisherInterface $publisher,
        MappingServiceInterface $mappingService
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalRegistry = $portalRegistry;
        $this->publisher = $publisher;
        $this->mappingService = $mappingService;
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey, ?array $dataTypes = null): void
    {
        $context = $this->exploreContextFactory->factory($portalNodeKey);
        $portal = $this->portalRegistry->getPortal($portalNodeKey);

        $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);

        $explorers = new ExplorerCollection();

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            $explorers->push($portalExtension->getExplorerDecorators());
        }

        $explorers->push($portal->getExplorers());

        $mappings = [];

        foreach (self::getSupportedTypes($explorers) as $supportedType) {
            if (\is_array($dataTypes) && !\in_array($supportedType, $dataTypes)) {
                continue;
            }

            $explorerStack = new ExplorerStack($explorers->bySupport($supportedType));

            /** @var DatasetEntityInterface|null $entity */
            foreach ($explorerStack->next($context) as $entity) {
                if (!$entity instanceof DatasetEntityInterface) {
                    continue;
                }

                $externalId = $entity->getPrimaryKey();

                if ($externalId === null) {
                    continue;
                }

                // TODO: use batch operations by using $this->mappingService->getListByExternalIds()
                $mappings[] = $this->mappingService->get(\get_class($entity), $portalNodeKey, $externalId);

                if (count($mappings) >= self::CHUNK_SIZE) {
                    $this->publisher->publishBatch(new MappingCollection($mappings));
                    $mappings = [];
                }
            }
        }

        if ($mappings) {
            $this->publisher->publishBatch(new MappingCollection($mappings));
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
