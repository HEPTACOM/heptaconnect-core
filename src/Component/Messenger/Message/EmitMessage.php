<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger\Message;

use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;

class EmitMessage
{
    private MappedDatasetEntityStruct $mappedDatasetEntityStruct;

    public function __construct(MappedDatasetEntityStruct $mappedDatasetEntityStruct)
    {
        $this->mappedDatasetEntityStruct = $mappedDatasetEntityStruct;
    }

    public function getMappedDatasetEntityStruct(): MappedDatasetEntityStruct
    {
        return $this->mappedDatasetEntityStruct;
    }
}
