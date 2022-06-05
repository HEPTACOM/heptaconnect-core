<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\MessageQueueFlow\Message;

use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;

class JobMessage
{
    private JobKeyCollection $jobKeys;

    public function __construct()
    {
        $this->jobKeys = new JobKeyCollection();
    }

    public function getJobKeys(): JobKeyCollection
    {
        return $this->jobKeys;
    }
}
