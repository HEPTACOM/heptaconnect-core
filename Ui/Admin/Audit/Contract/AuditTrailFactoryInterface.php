<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract;

use Heptacom\HeptaConnect\Ui\Admin\Base\Audit\UiAuditContext;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionInterface;

interface AuditTrailFactoryInterface
{
    /**
     * Creates an instance of @see AuditTrailInterface that allows for writing little code to add auditing to a UI action.
     */
    public function create(UiActionInterface $uiAction, UiAuditContext $auditContext, array $ingoing): AuditTrailInterface;
}
