<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

use Heptacom\HeptaConnect\Core\Emit\EmitResult;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

interface EmitServiceInteface
{
    public function emit(MappingCollection $mappings): EmitResult;
}
