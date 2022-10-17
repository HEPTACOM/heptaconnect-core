<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Portal\PortalEntityList\PortalEntityListCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\Portal\PortalEntityList\PortalEntityListResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeEntityList\PortalNodeEntityListCriteria;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\PortalNode\PortalNodeEntityList\PortalNodeEntityListResult;
use Heptacom\HeptaConnect\Ui\Admin\Base\Action\UiActionType;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\Portal\PortalEntityListUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\PortalNode\PortalNodeEntityListUiActionInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Exception\ReadException;

final class PortalEntityListUi implements PortalEntityListUiActionInterface
{
    public function __construct(private AuditTrailFactoryInterface $auditTrailFactory, private PortalNodeEntityListUiActionInterface $portalNodeEntityListUiAction)
    {
    }

    public static function class(): UiActionType
    {
        return new UiActionType(PortalEntityListUiActionInterface::class);
    }

    public function list(PortalEntityListCriteria $criteria, UiActionContextInterface $context): iterable
    {
        $trail = $this->auditTrailFactory->create($this, $context->getAuditContext(), [$criteria, $context]);
        $portalNodeKey = new PreviewPortalNodeKey($criteria->getPortal());

        $portalNodeCriteria = new PortalNodeEntityListCriteria($portalNodeKey);
        $portalNodeCriteria->setShowEmitter($criteria->getShowEmitter());
        $portalNodeCriteria->setShowExplorer($criteria->getShowExplorer());
        $portalNodeCriteria->setShowReceiver($criteria->getShowReceiver());
        $portalNodeCriteria->setFilterSupportedEntityType($criteria->getFilterSupportedEntityType());

        try {
            yield from $trail->returnIterable(\iterable_map(
                $this->portalNodeEntityListUiAction->list($portalNodeCriteria, $context),
                static fn (PortalNodeEntityListResult $result) => new PortalEntityListResult(
                    $result->getCodeOrigin(),
                    $result->getSupportedEntityType(),
                    $result->getFlowComponentClass()
                )
            ));
        } catch (\Throwable $throwable) {
            throw $trail->throwable(new ReadException(1663051795, $throwable));
        }
    }
}
