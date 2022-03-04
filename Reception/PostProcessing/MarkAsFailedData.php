<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;

class MarkAsFailedData
{
    private DatasetEntityContract $entity;

    private \Throwable $throwable;

    public function __construct(DatasetEntityContract $entity, \Throwable $throwable)
    {
        $this->entity = $entity;
        $this->throwable = $throwable;
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
