<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Ui\Admin\Audit;

use Heptacom\HeptaConnect\Core\Ui\Admin\Audit\Contract\AuditTrailInterface;

final class AuditTrail implements AuditTrailInterface
{
    private bool $hasEnded = false;

    public function __destruct()
    {
        if (!$this->hasEnded) {
            $this->end();
        }
    }

    public function return(object $result): object
    {
        $this->end();

        return $result;
    }

    public function returnIterable(iterable $result): iterable
    {
        try {
            yield from $result;

            $this->end();
        } catch (\Throwable $throwable) {
            throw $this->throwable($throwable);
        }
    }

    public function throwable(\Throwable $throwable): \Throwable
    {
        $this->end();

        return $throwable;
    }

    public function end(): void
    {
        $this->hasEnded = true;
    }
}
