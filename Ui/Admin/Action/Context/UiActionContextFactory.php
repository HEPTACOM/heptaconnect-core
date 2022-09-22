<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action\Context;

use Heptacom\HeptaConnect\Ui\Admin\Base\Audit\UiAuditContext;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextFactoryInterface;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class UiActionContextFactory implements UiActionContextFactoryInterface
{
    public function createContext(UiAuditContext $auditContext): UiActionContextInterface
    {
        return new UiActionContext($auditContext);
    }
}
