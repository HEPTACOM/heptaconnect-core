<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;

class JobData
{
    public function __construct(
        protected MappingComponentStructContract $mappingComponent,
        protected ?array $payload,
        private JobKeyInterface $jobKey
    ) {
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
