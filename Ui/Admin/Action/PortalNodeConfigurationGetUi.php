<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria as StoragePortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeConfigurationGet\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeConfigurationGetUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;

final class PortalNodeConfigurationGetUi implements PortalNodeConfigurationGetUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalNodeConfigurationGetActionInterface $configurationGetAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeConfigurationGetUiActionInterface::class);
    }

    public function get(PortalNodeConfigurationGetCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $pnKeysToLoad = new PortalNodeKeyCollection();

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if ($portalNodeKey instanceof PreviewPortalNodeKey) {
                yield $trail->yield(new PortalNodeConfigurationGetResult($portalNodeKey, []));
            } else {
                $pnKeysToLoad->push([$portalNodeKey]);
            }
        }

        if (!$pnKeysToLoad->isEmpty()) {
            $portalNodeGetCriteria = new PortalNodeGetCriteria($pnKeysToLoad);

            try {
                $gotPortalNodeKeys = new PortalNodeKeyCollection(\iterable_map(
                    $this->portalNodeGetAction->get($portalNodeGetCriteria),
                    static fn (PortalNodeGetResult $result): PortalNodeKeyInterface => $result->getPortalNodeKey()
                ));
            } catch (\Throwable $throwable) {
                throw $trail->throwable(new ReadException(1670832600, $throwable));
            }

            $missingPortalNodes = new PortalNodeKeyCollection($pnKeysToLoad->filter(
                static fn (PortalNodeKeyInterface $pnKey): bool => !$gotPortalNodeKeys->contains($pnKey)
            )->getIterator());

            if (!$missingPortalNodes->isEmpty()) {
                throw $trail->throwable(new PortalNodesMissingException($missingPortalNodes, 1670832601));
            }

            $fetchedPortalNodeKeys = new PortalNodeKeyCollection();

            try {
                $criteria = new StoragePortalNodeConfigurationGetCriteria($pnKeysToLoad);

                foreach ($this->configurationGetAction->get($criteria) as $configuration) {
                    $fetchedPortalNodeKeys->push([$configuration->getPortalNodeKey()]);

                    yield $trail->yield(new PortalNodeConfigurationGetResult(
                        $configuration->getPortalNodeKey(),
                        $configuration->getValue()
                    ));
                }
            } catch (\Throwable $throwable) {
                throw $trail->throwable(new ReadException(1670832602, $throwable));
            }

            $notFetchedPortalNodeKeys = $pnKeysToLoad->filter(
                static fn (PortalNodeKeyInterface $pnKey): bool => !$fetchedPortalNodeKeys->contains($pnKey)
            );

            foreach ($notFetchedPortalNodeKeys as $notFetchedPortalNodeKey) {
                yield $trail->yield(new PortalNodeConfigurationGetResult($notFetchedPortalNodeKey, []));
            }
        }

        $trail->end();
    }
}
