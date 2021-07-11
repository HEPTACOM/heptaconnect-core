<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class JobData
{
    protected MappingComponentStructContract $mappingComponent;

    protected array $payload;

    public function __construct(MappingComponentStructContract $mappingComponent, array $payload)
    {
        $this->mappingComponent = $mappingComponent;
        $this->payload = $payload;
    }

    public function getMappingComponent(): MappingComponentStructContract
    {
        return $this->mappingComponent;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
