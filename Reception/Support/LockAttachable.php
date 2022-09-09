<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Symfony\Component\Lock\LockInterface;

final class LockAttachable implements AttachableInterface
{
    private LockInterface $lock;

    public function __construct(LockInterface $lock)
    {
        $this->lock = $lock;
    }

    public function getLock(): LockInterface
    {
        return $this->lock;
    }
}
