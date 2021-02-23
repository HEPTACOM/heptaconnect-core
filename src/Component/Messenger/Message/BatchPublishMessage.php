<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Messenger\Message;

use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;

class BatchPublishMessage
{
    private MappingCollection $mappings;

    public function __construct(MappingCollection $mappings)
    {
        $this->mappings = $mappings;
    }

    public function getMappings(): MappingCollection
    {
        return $this->mappings;
    }
}
