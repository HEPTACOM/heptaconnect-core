<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;

interface ReceiveServiceInterface
{
    /**
     * @todo simplify saving of mappings / replace callback with return value
     */
    public function receive(TypedMappedDatasetEntityCollection $mappedDatasetEntities, callable $saveMappings): void;
}
