<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Symfony\Component\Lock\LockInterface;

final class LockAttachable implements AttachableInterface
{
    public function __construct(private LockInterface $lock)
    {
    }

    public function getLock(): LockInterface
    {
        return $this->lock;
    }
}
