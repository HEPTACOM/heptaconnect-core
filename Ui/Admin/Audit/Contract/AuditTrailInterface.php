<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract;

/**
 * Resembles a started UI audit trail with methods to modify its state.
 */
interface AuditTrailInterface
{
    /**
     * Mark the trail as ended.
     */
    public function end(): void;
}
