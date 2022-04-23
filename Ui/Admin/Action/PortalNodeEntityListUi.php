<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\FlowComponentRegistry;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverCodeOriginFinderInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeEntityList\PortalNodeEntityListCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeEntityList\PortalNodeEntityListResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeEntityListUiActionInterface;

final class PortalNodeEntityListUi implements PortalNodeEntityListUiActionInterface
{
    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder;

    private EmitterCodeOriginFinderInterface $emitterCodeOriginFinder;

    private ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder;

    public function __construct(
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder,
        EmitterCodeOriginFinderInterface $emitterCodeOriginFinder,
        ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder
    ) {
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->explorerCodeOriginFinder = $explorerCodeOriginFinder;
        $this->emitterCodeOriginFinder = $emitterCodeOriginFinder;
        $this->receiverCodeOriginFinder = $receiverCodeOriginFinder;
    }

    public function list(PortalNodeEntityListCriteria $criteria): iterable
    {
        $portalNodeKey = $criteria->getPortalNodeKey();
        $entityType = $criteria->getFilterSupportedEntityType();
        $entityFilter = static fn (iterable $fcs): iterable => $fcs;

        if ($entityType !== null) {
            $entityFilter = static fn (iterable $fcs): iterable => \iterable_filter(
                $fcs,
                static fn ($fc): bool => $fc->supports() === $entityType
            );
        }

        if ($criteria->getShowExplorer()) {
            /** @var ExplorerContract $flowComponent */
            foreach ($entityFilter($this->getExplorers($portalNodeKey)) as $flowComponent) {
                yield new PortalNodeEntityListResult(
                    $this->explorerCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->supports(),
                    ExplorerContract::class
                );
            }
        }

        if ($criteria->getShowEmitter()) {
            /** @var EmitterContract $flowComponent */
            foreach ($entityFilter($this->getEmitters($portalNodeKey)) as $flowComponent) {
                yield new PortalNodeEntityListResult(
                    $this->emitterCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->supports(),
                    EmitterContract::class
                );
            }
        }

        if ($criteria->getShowReceiver()) {
            /** @var ReceiverContract $flowComponent */
            foreach ($entityFilter($this->getReceivers($portalNodeKey)) as $flowComponent) {
                yield new PortalNodeEntityListResult(
                    $this->receiverCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->supports(),
                    ReceiverContract::class
                );
            }
        }
    }

    private function getExplorers(PortalNodeKeyInterface $portalNodeKey): ExplorerCollection
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);
        /** @var FlowComponentRegistry $flowComponentRegistry */
        $flowComponentRegistry = $container->get(FlowComponentRegistry::class);
        $components = new ExplorerCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getExplorers($source));
        }

        return $components;
    }

    private function getEmitters(PortalNodeKeyInterface $portalNodeKey): EmitterCollection
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);
        /** @var FlowComponentRegistry $flowComponentRegistry */
        $flowComponentRegistry = $container->get(FlowComponentRegistry::class);
        $components = new EmitterCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getEmitters($source));
        }

        return $components;
    }

    private function getReceivers(PortalNodeKeyInterface $portalNodeKey): ReceiverCollection
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);
        /** @var FlowComponentRegistry $flowComponentRegistry */
        $flowComponentRegistry = $container->get(FlowComponentRegistry::class);
        $components = new ReceiverCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getReceivers($source));
        }

        return $components;
    }
}
