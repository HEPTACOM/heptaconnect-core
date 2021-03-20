<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
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

    private EmitContextInterface $emitContext;

    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    private EmissionActorInterface $emissionActor;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalRegistryInterface $portalRegistry,
        PublisherInterface $publisher,
        MappingServiceInterface $mappingService,
        EmitContextInterface $emitContext,
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        EmissionActorInterface $emissionActor
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalRegistry = $portalRegistry;
        $this->publisher = $publisher;
        $this->mappingService = $mappingService;
        $this->emitContext = $emitContext;
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
        $this->emissionActor = $emissionActor;
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
            if (\is_array($dataTypes) && !\in_array($supportedType, $dataTypes, true)) {
                continue;
            }

            $explorerStack = new ExplorerStack($explorers->bySupport($supportedType));
            $directEmitter = new DirectEmitter($supportedType);
            $emissionStack = $this->emitterStackBuilderFactory
                ->createEmitterStackBuilder($portalNodeKey, $supportedType)
                ->push($directEmitter)
                ->pushDecorators()
                ->build();

            /** @var DatasetEntityContract|string|int|null $entity */
            foreach ($explorerStack->next($context) as $entity) {
                if ($entity instanceof DatasetEntityContract && ($primaryKey = $entity->getPrimaryKey()) !== null) {
                    $mapping = $this->mappingService->get($supportedType, $portalNodeKey, $primaryKey);

                    $directEmitter->getMappedEntities()->clear();
                    $directEmitter->getMappedEntities()->push([new MappedDatasetEntityStruct($mapping, $entity)]);
                    $this->emissionActor->performEmission(
                        new TypedMappingCollection($supportedType, [$mapping]),
                        clone $emissionStack,
                        $this->emitContext,
                    );
                } elseif (\is_string($entity) || \is_int($entity)) {
                    // TODO: use batch operations by using $this->mappingService->getListByExternalIds()
                    $mappings[] = $this->mappingService->get($supportedType, $portalNodeKey, (string) $entity);

                    if (\count($mappings) >= self::CHUNK_SIZE) {
                        $this->publisher->publishBatch(new MappingCollection($mappings));
                        $mappings = [];
                    }
                }
                // TODO: log this
            }
        }

        if ($mappings) {
            $this->publisher->publishBatch(new MappingCollection($mappings));
        }
    }

    /**
     * @psalm-return array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>>
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
