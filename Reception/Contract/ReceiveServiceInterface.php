<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;

interface ReceiveServiceInterface
{
    public function receive(TypedMappedDatasetEntityCollection $mappedDatasetEntities): void;
}
