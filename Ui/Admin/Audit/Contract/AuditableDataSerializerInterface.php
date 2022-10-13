<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract;

use Heptacom\HeptaConnect\Ui\Admin\Base\Contract\Audit\AuditableDataAwareInterface;

interface AuditableDataSerializerInterface
{
    /**
     * Serializes the given auditable data aware item.
     */
    public function serialize(AuditableDataAwareInterface $auditableDataAware): string;
}
