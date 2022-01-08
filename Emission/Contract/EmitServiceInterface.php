<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;

interface EmitServiceInterface
{
    public function emit(TypedMappingComponentCollection $mappingComponents): void;
}
