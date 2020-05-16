<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger\Message;

use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;

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