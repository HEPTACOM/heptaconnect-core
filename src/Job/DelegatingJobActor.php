<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionJobHandler $publishJobHandler;

    public function __construct(EmissionJobHandler $publishJobHandler)
    {
        $this->publishJobHandler = $publishJobHandler;
    }

    public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): void
    {
        switch ($type) {
            case Emission::TYPE:
                $this->publishJobHandler->publish($mapping);
                break;
        }
    }
}
