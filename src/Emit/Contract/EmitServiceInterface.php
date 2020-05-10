<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

interface EmitServiceInterface
{
    public function emit(MappingCollection $mappings): void;
}
