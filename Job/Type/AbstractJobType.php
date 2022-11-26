<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Type;

use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

abstract class AbstractJobType extends JobContract
{
    public function __construct(
        protected MappingComponentStructContract $mapping
    ) {
    }

    public function getMappingComponent(): MappingComponentStructContract
    {
        return $this->mapping;
    }
}
