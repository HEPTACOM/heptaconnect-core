<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger\Message;

use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;

class EmitMessage
{
    private MappedDatasetEntityStruct $mappedDatasetEntityStruct;

    private TrackedEntityCollection $trackedEntities;

    public function __construct(MappedDatasetEntityStruct $mappedDatasetEntityStruct, TrackedEntityCollection $trackedEntities)
    {
        $this->mappedDatasetEntityStruct = $mappedDatasetEntityStruct;
        $this->trackedEntities = $trackedEntities;
    }

    public function getMappedDatasetEntityStruct(): MappedDatasetEntityStruct
    {
        return $this->mappedDatasetEntityStruct;
    }

    public function getTrackedEntities(): TrackedEntityCollection
    {
        return $this->trackedEntities;
    }
}
