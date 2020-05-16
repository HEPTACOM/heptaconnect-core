<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

use Heptacom\HeptaConnect\Portal\Base\TypedMappingCollection;

interface EmitServiceInterface
{
    public function emit(TypedMappingCollection $mappings): void;
}
