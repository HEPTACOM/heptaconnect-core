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
     * Log and return the given result object and mark the audit trail as ended.
     *
     * @template TResult of object
     *
     * @param TResult $result
     *
     * @return TResult
     */
    public function return(object $result): object;

    /**
     * Log and return the given result object, but does not mark the audit trail as ended
     * as more calls of this are expected and a closing @see end
     *
     * @template TResult of object
     *
     * @param TResult $result
     *
     * @return TResult
     */
    public function yield(object $result): object;

    /**
     * Log and return the given result objects and mark the audit trail as ended.
     *
     * @template TResult of object
     *
     * @param iterable<array-key, TResult> $result
     *
     * @return iterable<array-key, TResult>
     */
    public function returnIterable(iterable $result): iterable;

    /**
     * Mark the trail as ended.
     */
    public function end(): void;
}
