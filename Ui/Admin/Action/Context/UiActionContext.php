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

    public function __construct(
        private UiAuditContext $auditContext
    ) {
        $this->attachments = new AttachmentCollection();
    }

    public function getAuditContext(): UiAuditContext
    {
        return $this->auditContext;
    }
}
