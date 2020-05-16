<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive\Contract;

use Heptacom\HeptaConnect\Portal\Base\TypedMappedDatasetEntityCollection;

interface ReceiveServiceInterface
{
    public function receive(TypedMappedDatasetEntityCollection $mappedDatasetEntities): void;
}
