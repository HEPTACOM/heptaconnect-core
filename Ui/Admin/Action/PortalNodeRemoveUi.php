<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Support\Contract\PortalNodeExistenceSeparatorInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeRemove\PortalNodeRemoveCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeRemoveUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\PersistException;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;

final class PortalNodeRemoveUi implements PortalNodeRemoveUiActionInterface
{
    public function __construct(
        private AuditTrailFactoryInterface $auditTrailFactory,
        private PortalNodeExistenceSeparatorInterface $portalNodeExistenceSeparator,
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

        try {
            $separation = $this->portalNodeExistenceSeparator->separateKeys($criteria->getPortalNodeKeys());
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1650758000, $throwable));
        }

        $separation->throwWhenKeysAreMissing($trail);
        $separation->throwWhenPreviewKeysAreGiven($trail);

        try {
            $this->portalNodeDeleteAction->delete(new PortalNodeDeleteCriteria($separation->getExistingKeys()));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new PersistException(1650758002, $throwable));
        }

        $trail->end();
    }
}
