<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler;
use Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionHandler $emissionHandler;

    private ReceptionHandler $receptionHandler;

    public function __construct(EmissionHandler $emissionHandler, ReceptionHandler $receptionHandler)
    {
        $this->emissionHandler = $emissionHandler;
        $this->receptionHandler = $receptionHandler;
    }

    public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): bool
    {
        switch ($type) {
            case Emission::class:
                return $this->emissionHandler->triggerEmission($mapping);
            case Reception::class:
                return $this->receptionHandler->triggerReception($mapping, $payload);
        }

        // TODO error log
        return true;
    }
}
