<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler;
use Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionHandler $emissionHandler;

    private ReceptionHandler $receptionHandler;

    public function __construct(EmissionHandler $emissionHandler, ReceptionHandler $receptionHandler)
    {
        $this->emissionHandler = $emissionHandler;
        $this->receptionHandler = $receptionHandler;
    }

    public function performJobs(string $type, JobDataCollection $jobs): void
    {
        switch ($type) {
            case Emission::class:
                $this->emissionHandler->triggerEmission($jobs);
                break;
            case Reception::class:
                $this->receptionHandler->triggerReception($jobs);
                break;
        }
    }
}
