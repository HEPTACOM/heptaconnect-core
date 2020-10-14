<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeNotCreatedException;
use Heptacom\HeptaConnect\Core\Mapping\MappingStruct;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;

class ExploreService implements ExploreServiceInterface
{
    public const CHUNK_SIZE = 50;

    private ExploreContextFactoryInterface $exploreContextFactory;

    private PortalRegistryInterface $portalRegistry;

    private PublisherInterface $publisher;

    private MappingRepositoryContract $mappingRepository;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalRegistryInterface $portalRegistry,
        PublisherInterface $publisher,
        MappingRepositoryContract $mappingRepository,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalRegistry = $portalRegistry;
        $this->publisher = $publisher;
        $this->mappingRepository = $mappingRepository;
        $this->mappingNodeRepository = $mappingNodeRepository;
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

        $mappings = [];

        foreach (self::getSupportedTypes($explorers) as $supportedType) {
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

                $mappings[] = $this->getMapping($entity, $portalNodeKey, $externalId);

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

    private function getMappingNodeId(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): ?MappingNodeKeyInterface {
        $ids = $this->mappingNodeRepository->listByTypeAndPortalNodeAndExternalId(
            $datasetEntityClassName,
            $portalNodeKey,
            $externalId
        );

        foreach ($ids as $id) {
            return $id;
        }

        return null;
    }

    private function getMapping(
        ?DatasetEntityInterface $entity,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ) {
        $datasetEntityClassName = \get_class($entity);

        $mappingNodeId = $this->getMappingNodeId($datasetEntityClassName, $portalNodeKey, $externalId);
        $mappingExists = $mappingNodeId instanceof MappingNodeKeyInterface;

        if (!$mappingExists) {
            $mappingNodeId = $this->mappingNodeRepository->create($datasetEntityClassName, $portalNodeKey);
        }

        if (!$mappingNodeId instanceof MappingNodeKeyInterface) {
            throw new MappingNodeNotCreatedException();
        }

        $mapping = (new MappingStruct($portalNodeKey, $this->mappingNodeRepository->read($mappingNodeId)))
            ->setExternalId($externalId);

        if (!$mappingExists) {
            $this->mappingRepository->create(
                $mapping->getPortalNodeKey(),
                $mapping->getMappingNodeKey(),
                $mapping->getExternalId()
            );
        }

        return $mapping;
    }
}
