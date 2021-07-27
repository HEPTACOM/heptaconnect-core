<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;

class SaveMappingsData
{

    private DatasetEntityContract $entity;

    public function __construct(DatasetEntityContract $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): DatasetEntityContract
    {
        return $this->entity;
    }


}
