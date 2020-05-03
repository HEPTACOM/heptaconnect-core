<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive\Contract;

use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityCollection;

interface ReceiveServiceInterface
{
    public function receive(MappedDatasetEntityCollection $mappedDatasetEntities): void;
}
