<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;


class NullPostProcessorData
{
    private string $entity;

    public function __construct(string $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }
}
