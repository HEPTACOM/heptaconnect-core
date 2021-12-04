<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

abstract class JobContract
{
    abstract public function getMappingComponent(): MappingComponentStructContract;

    public function getPayload(): ?array
    {
        return null;
    }

    public function getType(): string
    {
        return static::class;
    }
}
