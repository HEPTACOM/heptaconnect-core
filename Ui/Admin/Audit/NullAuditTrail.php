<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;

final class NullAuditTrail implements AuditTrailInterface
{
    public function return(object $result): object
    {
        return $result;
    }

    public function yield(object $result): object
    {
        return $result;
    }

    public function returnIterable(iterable $result): iterable
    {
        yield from $result;
    }

    public function throwable(\Throwable $throwable): \Throwable
    {
        return $throwable;
    }

    public function end(): void
    {
    }
}
