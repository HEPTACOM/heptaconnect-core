<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
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

    public function get(
        PortalNodeConfigurationGetCriteria $criteria,
        UiActionContextInterface $context
    ): PortalNodeConfigurationGetResult {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $portalNodeKey = $criteria->getPortalNodeKey();

        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return $trail->return(new PortalNodeConfigurationGetResult($portalNodeKey, []));
        }

        $portalNodeGetCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        try {
            $portalNodes = \iterable_to_array($this->portalNodeGetAction->get($portalNodeGetCriteria));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1670832600, $throwable));
        }

        $portalNode = \array_shift($portalNodes);

        if ($portalNode === null) {
            throw $trail->throwable(
                new PortalNodesMissingException($portalNodeGetCriteria->getPortalNodeKeys(), 1670832601)
            );
        }

        try {
            return $trail->return(new PortalNodeConfigurationGetResult(
                $portalNodeKey,
                $this->getPortalNodeConfigurationInternal($portalNodeKey)
            ));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1670832602, $throwable));
        }
    }

    /**
     * @throws \Throwable
     */
    private function getPortalNodeConfigurationInternal(PortalNodeKeyInterface $portalNodeKey): array
    {
        $criteria = new StoragePortalNodeConfigurationGetCriteria(new PortalNodeKeyCollection([$portalNodeKey]));

        foreach ($this->configurationGetAction->get($criteria) as $configuration) {
            return $configuration->getValue();
        }

        return [];
    }
}
