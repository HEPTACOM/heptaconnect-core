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
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Portal\PortalEntityList\PortalEntityListCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Portal\PortalEntityList\PortalEntityListResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Portal\PortalEntityListUiActionInterface;

final class PortalEntityListUi implements PortalEntityListUiActionInterface
{
    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    private PortalNodeDeleteActionInterface $portalNodeDeleteAction;

    private ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder;

    private EmitterCodeOriginFinderInterface $emitterCodeOriginFinder;

    private ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder;

    public function __construct(
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        PortalNodeCreateActionInterface $portalNodeCreateAction,
        PortalNodeDeleteActionInterface $portalNodeDeleteAction,
        ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder,
        EmitterCodeOriginFinderInterface $emitterCodeOriginFinder,
        ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder
    ) {
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->portalNodeCreateAction = $portalNodeCreateAction;
        $this->portalNodeDeleteAction = $portalNodeDeleteAction;
        $this->explorerCodeOriginFinder = $explorerCodeOriginFinder;
        $this->emitterCodeOriginFinder = $emitterCodeOriginFinder;
        $this->receiverCodeOriginFinder = $receiverCodeOriginFinder;
    }

    public function list(PortalEntityListCriteria $criteria): iterable
    {
        $portalNodeKey = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload($criteria->getPortal()),
        ]))->first()->getPortalNodeKey();

        $entityType = $criteria->getFilterSupportedEntityType();
        $entityFilter = static fn (iterable $fcs): iterable => $fcs;

        if ($entityType !== null) {
            $entityFilter = static fn (iterable $fcs): iterable => \iterable_filter(
                $fcs,
                static fn ($fc): bool => $fc->supports() === $entityType
            );
        }

        try {
            if ($criteria->getShowExplorer()) {
                /** @var ExplorerContract $flowComponent */
                foreach ($entityFilter($this->getExplorers($portalNodeKey)) as $flowComponent) {
                    yield new PortalEntityListResult(
                        $this->explorerCodeOriginFinder->findOrigin($flowComponent),
                        $flowComponent->supports(),
                        ExplorerContract::class
                    );
                }
            }

            if ($criteria->getShowEmitter()) {
                /** @var EmitterContract $flowComponent */
                foreach ($entityFilter($this->getEmitters($portalNodeKey)) as $flowComponent) {
                    yield new PortalEntityListResult(
                        $this->emitterCodeOriginFinder->findOrigin($flowComponent),
                        $flowComponent->supports(),
                        EmitterContract::class
                    );
                }
            }

            if ($criteria->getShowReceiver()) {
                /** @var ReceiverContract $flowComponent */
                foreach ($entityFilter($this->getReceivers($portalNodeKey)) as $flowComponent) {
                    yield new PortalEntityListResult(
                        $this->receiverCodeOriginFinder->findOrigin($flowComponent),
                        $flowComponent->supports(),
                        ReceiverContract::class
                    );
                }
            }
        } finally {
            $this->portalNodeDeleteAction->delete(new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([$portalNodeKey])));
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
