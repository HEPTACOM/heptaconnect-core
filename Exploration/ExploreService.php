<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Exploration;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class ExploreService implements ExploreServiceInterface
{
    private ExploreContextFactoryInterface $exploreContextFactory;

    private ExplorationActorInterface $explorationActor;

    private ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private LoggerInterface $logger;

    private JobDispatcherContract $jobDispatcher;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        ExplorationActorInterface $explorationActor,
        ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        LoggerInterface $logger,
        JobDispatcherContract $jobDispatcher
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->explorationActor = $explorationActor;
        $this->explorerStackBuilderFactory = $explorerStackBuilderFactory;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->logger = $logger;
        $this->jobDispatcher = $jobDispatcher;
    }

    public function dispatchExploreJob(PortalNodeKeyInterface $portalNodeKey, ?EntityTypeCollection $entityTypes = null): void
    {
        $jobs = new JobCollection();

        foreach (self::getSupportedTypes($this->getExplorers($portalNodeKey)) as $supportedType) {
            if ($entityTypes !== null && !$entityTypes->has($supportedType)) {
                continue;
            }

            $jobs->push([new Exploration(new MappingComponentStruct($portalNodeKey, $supportedType, $supportedType . '_NO_ID'))]);
        }

        $this->jobDispatcher->dispatch($jobs);
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey, ?EntityTypeCollection $entityTypes = null): void
    {
        $context = $this->exploreContextFactory->factory($portalNodeKey);

        foreach (self::getSupportedTypes($this->getExplorers($portalNodeKey)) as $supportedType) {
            if ($entityTypes !== null && !$entityTypes->has($supportedType)) {
                continue;
            }

            $builder = $this->explorerStackBuilderFactory
                ->createExplorerStackBuilder($portalNodeKey, $supportedType)
                ->pushSource()
                ->pushDecorators();

            if ($builder->isEmpty()) {
                $this->logger->critical(LogMessage::EXPLORE_NO_EXPLORER_FOR_TYPE(), [
                    'type' => $supportedType,
                ]);

                continue;
            }

            $this->explorationActor->performExploration($supportedType, $builder->build(), $context);
        }
    }

    protected static function getSupportedTypes(ExplorerCollection $explorers): EntityTypeCollection
    {
        return new EntityTypeCollection($explorers->column('getSupportedEntityType'));
    }

    protected function getExplorers(PortalNodeKeyInterface $portalNodeKey): ExplorerCollection
    {
        $flowComponentRegistry = $this->portalStackServiceContainerFactory
            ->create($portalNodeKey)
            ->getFlowComponentRegistry();
        $components = new ExplorerCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getExplorers($source));
        }

        return $components;
    }
}
