<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;

interface EmitServiceInterface
{
    public function emit(TypedMappingCollection $mappings): void;
}
