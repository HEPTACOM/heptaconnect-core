<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeRemove\PortalNodeRemoveCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeRemoveUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PortalNodesMissingException;

final class PortalNodeRemoveUi implements PortalNodeRemoveUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeGetActionInterface $portalNodeGetAction,
        private PortalNodeDeleteActionInterface $portalNodeDeleteAction
    ) {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalNodeRemoveUiActionInterface::class);
    }

    public function remove(PortalNodeRemoveCriteria $criteria, UiActionContextInterface $context): void
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $uncheckedPortalNodeKeys = $criteria->getPortalNodeKeys()->asUnique();

        try {
            $foundPortalNodes = $this->portalNodeGetAction->get(new PortalNodeGetCriteria($criteria->getPortalNodeKeys()));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1650758000, $throwable));
        }

        foreach ($foundPortalNodes as $foundPortalNode) {
            $uncheckedPortalNodeKeys = $uncheckedPortalNodeKeys->filter(
                static fn (PortalNodeKeyInterface $key): bool => !$key->equals($foundPortalNode->getPortalNodeKey())
            );
        }

        if (!$uncheckedPortalNodeKeys->isEmpty()) {
            throw $trail->throwable(new PortalNodesMissingException($uncheckedPortalNodeKeys, 1650758001));
        }

        try {
            $this->portalNodeDeleteAction->delete(new PortalNodeDeleteCriteria($criteria->getPortalNodeKeys()));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1650758002, $throwable));
        }

        $trail->end();
    }
}
