<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;

interface EmitServiceInteface
{
    public function emit(MappingCollection $mappings): MappedDatasetEntityCollection;
}
