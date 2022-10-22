<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Portal\FlowComponentRegistry;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
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
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeEntityListUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class PortalNodeEntityListUi implements PortalNodeEntityListUiActionInterface
{
    private AuditTrailFactoryInterface $auditTrailFactory;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    private ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder;

    private EmitterCodeOriginFinderInterface $emitterCodeOriginFinder;

    private ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder;

    public function __construct(
        AuditTrailFactoryInterface $auditTrailFactory,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory,
        ExplorerCodeOriginFinderInterface $explorerCodeOriginFinder,
        EmitterCodeOriginFinderInterface $emitterCodeOriginFinder,
        ReceiverCodeOriginFinderInterface $receiverCodeOriginFinder
    ) {
        $this->auditTrailFactory = $auditTrailFactory;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
        $this->explorerCodeOriginFinder = $explorerCodeOriginFinder;
        $this->emitterCodeOriginFinder = $emitterCodeOriginFinder;
        $this->receiverCodeOriginFinder = $receiverCodeOriginFinder;
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeEntityListUiActionInterface::class);
    }

    public function list(PortalNodeEntityListCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $portalNodeKey = $criteria->getPortalNodeKey();
        $entityType = $criteria->getFilterSupportedEntityType();
        $entityFilter = static fn (iterable $fcs): iterable => $fcs;

        if ($entityType !== null) {
            $entityFilter = static fn (iterable $fcs): iterable => \iterable_filter(
                $fcs,
                static fn ($fc): bool => $fc->getSupportedEntityType()->equals($entityType)
            );
        }

        if ($criteria->getShowExplorer()) {
            /** @var ExplorerContract $flowComponent */
            foreach ($entityFilter($this->getExplorers($portalNodeKey)) as $flowComponent) {
                yield $trail->yield(new PortalNodeEntityListResult(
                    $this->explorerCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->getSupportedEntityType(),
                    ExplorerContract::class
                ));
            }
        }

        if ($criteria->getShowEmitter()) {
            /** @var EmitterContract $flowComponent */
            foreach ($entityFilter($this->getEmitters($portalNodeKey)) as $flowComponent) {
                yield $trail->yield(new PortalNodeEntityListResult(
                    $this->emitterCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->getSupportedEntityType(),
                    EmitterContract::class
                ));
            }
        }

        if ($criteria->getShowReceiver()) {
            /** @var ReceiverContract $flowComponent */
            foreach ($entityFilter($this->getReceivers($portalNodeKey)) as $flowComponent) {
                yield $trail->yield(new PortalNodeEntityListResult(
                    $this->receiverCodeOriginFinder->findOrigin($flowComponent),
                    $flowComponent->getSupportedEntityType(),
                    ReceiverContract::class
                ));
            }
        }

        $trail->end();
    }

    private function getExplorers(PortalNodeKeyInterface $portalNodeKey): ExplorerCollection
    {
        $container = $this->portalStackServiceContainerFactory->create($portalNodeKey);
        $flowComponentRegistry = $container->getFlowComponentRegistry();
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
