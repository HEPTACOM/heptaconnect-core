<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Action\Context;

use Heptacom\HeptaConnect\Dataset\Base\AttachmentCollection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachmentAwareInterface;
use Heptacom\HeptaConnect\Dataset\Base\Support\AttachmentAwareTrait;
use Heptacom\HeptaConnect\Ui\Admin\Base\Audit\UiAuditContext;
use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Action\UiActionContextInterface;

final class UiActionContext implements AttachmentAwareInterface, UiActionContextInterface
{
    use AttachmentAwareTrait;

    private UiAuditContext $auditContext;

    public function __construct(UiAuditContext $auditContext)
    {
        $this->attachments = new AttachmentCollection();
        $this->auditContext = $auditContext;
    }

    public function getAuditContext(): UiAuditContext
    {
        return $this->auditContext;
    }
}
