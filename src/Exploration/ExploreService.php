<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class ExploreService implements ExploreServiceInterface
{
    private ExploreContextFactoryInterface $exploreContextFactory;

    private ExplorationActorInterface $explorationActor;

    private ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private LoggerInterface $logger;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        ExplorationActorInterface $explorationActor,
        ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        LoggerInterface $logger
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->explorationActor = $explorationActor;
        $this->explorerStackBuilderFactory = $explorerStackBuilderFactory;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->logger = $logger;
    }

    public function explore(PortalNodeKeyInterface $portalNodeKey, ?array $dataTypes = null): void
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);

        /** @var ExplorerCollection $explorers */
        $explorers = $container->get(ExplorerCollection::class);
        /** @var ExplorerCollection $explorerDecorators */
        $explorerDecorators = $container->get(ExplorerCollection::class.'.decorator');
        $explorers->push($explorerDecorators);

        $context = $this->exploreContextFactory->factory($portalNodeKey);

        foreach (self::getSupportedTypes($explorers) as $supportedType) {
            if (\is_array($dataTypes) && !\in_array($supportedType, $dataTypes, true)) {
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
