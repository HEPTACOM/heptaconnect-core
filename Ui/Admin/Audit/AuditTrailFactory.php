<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailFactoryInterface;
use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Audit\UiAuditContext;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionInterface;

final class AuditTrailFactory implements AuditTrailFactoryInterface
{
    public function create(UiActionInterface $uiAction, UiAuditContext $auditContext, array $ingoing): AuditTrailInterface
    {
        return new AuditTrail();
    }
}
