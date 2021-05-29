<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Type;

class Publish extends AbstractJobType
{
    public const TYPE = self::class;

    public function getType(): string
    {
        return self::TYPE;
    }
}
