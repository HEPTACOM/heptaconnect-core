<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Job;

use Heptacom\HeptaConnect\Core\Job\Contract\DelegatingJobActorContract;
use Heptacom\HeptaConnect\Core\Job\Handler\EmissionHandler;
use Heptacom\HeptaConnect\Core\Job\Handler\ExplorationHandler;
use Heptacom\HeptaConnect\Core\Job\Handler\ReceptionHandler;
use Heptacom\HeptaConnect\Core\Job\Type\Emission;
use Heptacom\HeptaConnect\Core\Job\Type\Exploration;
use Heptacom\HeptaConnect\Core\Job\Type\Reception;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionHandler $emissionHandler;

    private ReceptionHandler $receptionHandler;

    private ExplorationHandler $explorationHandler;

    public function __construct(
        EmissionHandler $emissionHandler,
        ReceptionHandler $receptionHandler,
        ExplorationHandler $explorationHandler
    ) {
        $this->emissionHandler = $emissionHandler;
        $this->receptionHandler = $receptionHandler;
        $this->explorationHandler = $explorationHandler;
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
            case Exploration::class:
                $this->explorationHandler->triggerExplorations($jobs);
                break;
        }
    }
}
