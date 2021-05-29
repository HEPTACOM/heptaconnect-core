<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Type\Publish;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private PublishJobHandler $publishJobHandler;

    public function __construct(PublishJobHandler $publishJobHandler)
    {
        $this->publishJobHandler = $publishJobHandler;
    }

    public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): void
    {
        switch ($type) {
            case Publish::TYPE:
                $this->publishJobHandler->publish($mapping);
                break;
        }
    }
}
