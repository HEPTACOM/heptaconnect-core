<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;

class JobData
{
    protected MappingComponentStructContract $mappingComponent;

    protected ?array $payload;

    private JobKeyInterface $jobKey;

    public function __construct(MappingComponentStructContract $mappingComponent, ?array $payload, JobKeyInterface $jobKey)
    {
        $this->mappingComponent = $mappingComponent;
        $this->payload = $payload;
        $this->jobKey = $jobKey;
    }

    public function getMappingComponent(): MappingComponentStructContract
    {
        return $this->mappingComponent;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function getJobKey(): JobKeyInterface
    {
        return $this->jobKey;
    }
}
