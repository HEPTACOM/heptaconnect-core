<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationFlowExplorersFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\JobCollection;
use Heptacom\HeptaConnect\Core\Job\Type\Exploration;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\EntityTypeCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

final class ExploreService implements ExploreServiceInterface
{
    public function __construct(
        private ExploreContextFactoryInterface $exploreContextFactory,
        private ExplorerStackProcessorInterface $explorerStackProcessor,
        private ExplorationFlowExplorersFactoryInterface $explorationFlowExplorersFactory,
        private ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory,
        private PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        private LoggerInterface $logger,
        private JobDispatcherContract $jobDispatcher
    ) {
    }

    public function dispatchExploreJob(PortalNodeKeyInterface $portalNodeKey, ?EntityTypeCollection $entityTypes = null): void
    {
        $jobs = new JobCollection();

        foreach (self::getSupportedTypes($this->getExplorers($portalNodeKey)) as $supportedType) {
            if ($entityTypes !== null && !$entityTypes->contains($supportedType)) {
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
            if ($entityTypes !== null && !$entityTypes->contains($supportedType)) {
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

            foreach ($this->explorationFlowExplorersFactory->createExplorers($portalNodeKey, $supportedType) as $explorer) {
                $builder = $builder->push($explorer);
            }

            $this->explorerStackProcessor->processStack($builder->build(), $context);
        }
    }

    private static function getSupportedTypes(ExplorerCollection $explorers): EntityTypeCollection
    {
        return new EntityTypeCollection($explorers->map(
            static fn (ExplorerContract $explorer): EntityType => $explorer->getSupportedEntityType()
        ));
    }

    private function getExplorers(PortalNodeKeyInterface $portalNodeKey): ExplorerCollection
    {
        $flowComponentRegistry = $this->portalStackServiceContainerFactory
            ->create($portalNodeKey)
            ->getFlowComponentRegistry();

        return $flowComponentRegistry->getExplorers();
    }
}
