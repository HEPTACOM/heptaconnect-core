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

    public function end(): void
    {
        $this->hasEnded = true;
    }
}
