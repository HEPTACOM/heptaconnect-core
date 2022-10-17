<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;

class SaveMappingsData
{
    public function __construct(private DatasetEntityContract $entity)
    {
    }

    public function getEntity(): DatasetEntityContract
    {
        return $this->entity;
    }
}
