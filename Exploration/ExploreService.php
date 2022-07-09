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
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeClassString;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
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

    public function dispatchExploreJob(PortalNodeKeyInterface $portalNodeKey, ?array $dataTypes = null): void
    {
        $jobs = new JobCollection();

        foreach (self::getSupportedTypes($this->getExplorers($portalNodeKey)) as $supportedType => $supportedEntityType) {
            if (\is_array($dataTypes) && !\in_array($supportedType, $dataTypes, true)) {
                continue;
            }

            $jobs->push([new Exploration(new MappingComponentStruct($portalNodeKey, $supportedEntityType, $supportedEntityType . '_NO_ID'))]);
        }

        $this->jobDispatcher->dispatch($jobs);
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey, ?array $dataTypes = null): void
    {
        $context = $this->exploreContextFactory->factory($portalNodeKey);

        foreach (self::getSupportedTypes($this->getExplorers($portalNodeKey)) as $supportedType => $supportedEntityType) {
            if (\is_array($dataTypes) && !\in_array($supportedType, $dataTypes, true)) {
                continue;
            }

            $builder = $this->explorerStackBuilderFactory
                ->createExplorerStackBuilder($portalNodeKey, $supportedEntityType)
                ->pushSource()
                ->pushDecorators();

            if ($builder->isEmpty()) {
                $this->logger->critical(LogMessage::EXPLORE_NO_EXPLORER_FOR_TYPE(), [
                    'type' => $supportedEntityType,
                ]);

                continue;
            }

            $this->explorationActor->performExploration($supportedEntityType, $builder->build(), $context);
        }
    }

    /**
     * @return array<class-string, EntityTypeClassString>
     */
    protected static function getSupportedTypes(ExplorerCollection $explorers): array
    {
        $types = [];

        /** @var ExplorerContract $explorer */
        foreach ($explorers as $explorer) {
            $type = $explorer->getSupportedEntityType();
            $types[$type->getClassString()] = $type;
        }

        return $types;
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
