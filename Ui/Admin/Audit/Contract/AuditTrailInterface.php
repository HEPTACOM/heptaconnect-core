<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract;

/**
 * Resembles a started UI audit trail with methods to modify its state.
 */
interface AuditTrailInterface
{
    /**
     * Log and return the given throwable and mark the audit trail as ended.
     *
     * @template TThrowable of \Throwable
     *
     * @param TThrowable $throwable
     *
     * @return TThrowable
     */
    public function throwable(\Throwable $throwable): \Throwable;

    /**
     * Mark the trail as ended.
     */
    public function end(): void;
}
