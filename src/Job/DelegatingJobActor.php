<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionHandler $emissionHandler;

    public function __construct(EmissionHandler $emissionHandler)
    {
        $this->emissionHandler = $emissionHandler;
    }

    public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): bool
    {
        switch ($type) {
            case Emission::class:
                return $this->emissionHandler->triggerEmission($mapping);
        }

        // TODO error log
        return true;
    }
}
