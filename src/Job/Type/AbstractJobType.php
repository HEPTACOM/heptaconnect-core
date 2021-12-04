<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job\Type;

use Heptacom\HeptaConnect\Core\Job\Contract\JobContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

abstract class AbstractJobType extends JobContract
{
    protected MappingComponentStructContract $mapping;

    public function __construct(MappingComponentStructContract $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getMappingComponent(): MappingComponentStructContract
    {
        return $this->mapping;
    }
}
