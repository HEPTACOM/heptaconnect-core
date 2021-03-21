<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExploreServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class ExploreService implements ExploreServiceInterface
{
    private ExploreContextFactoryInterface $exploreContextFactory;

    private PortalRegistryInterface $portalRegistry;

    private ExplorationActorInterface $explorationActor;

    private ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory;

    private LoggerInterface $logger;

    public function __construct(
        ExploreContextFactoryInterface $exploreContextFactory,
        PortalRegistryInterface $portalRegistry,
        ExplorationActorInterface $explorationActor,
        ExplorerStackBuilderFactoryInterface $explorerStackBuilderFactory,
        LoggerInterface $logger
    ) {
        $this->exploreContextFactory = $exploreContextFactory;
        $this->portalRegistry = $portalRegistry;
        $this->explorationActor = $explorationActor;
        $this->explorerStackBuilderFactory = $explorerStackBuilderFactory;
        $this->logger = $logger;
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
