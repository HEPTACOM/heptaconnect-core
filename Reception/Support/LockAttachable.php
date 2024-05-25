<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

use Heptacom\HeptaConnect\Utility\Attachment\Contract\AttachableInterface;
use Symfony\Component\Lock\LockInterface;

final readonly class LockAttachable implements AttachableInterface
{
    public function __construct(
        private LockInterface $lock
    ) {
    }

    public function getLock(): LockInterface
    {
        return $this->lock;
    }
}
