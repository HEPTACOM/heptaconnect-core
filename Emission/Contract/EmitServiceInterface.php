<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;

interface EmitServiceInterface
{
    /**
     * Executes an emission for the given mapping components through a stack of @see EmitterContract for the given portal node stack.
     */
    public function emit(TypedMappingComponentCollection $mappingComponents): void;
}
