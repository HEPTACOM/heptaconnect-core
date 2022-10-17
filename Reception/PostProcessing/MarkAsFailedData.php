<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;

class MarkAsFailedData
{
    public function __construct(private DatasetEntityContract $entity, private \Throwable $throwable)
    {
    }

    public function getEntity(): DatasetEntityContract
    {
        return $this->entity;
    }

    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
