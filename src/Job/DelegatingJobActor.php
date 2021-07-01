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
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Psr\Log\LoggerInterface;

class DelegatingJobActor extends DelegatingJobActorContract
{
    private EmissionHandler $emissionHandler;

    private ReceptionHandler $receptionHandler;

    private ExplorationHandler $explorationHandler;

    private LoggerInterface $logger;


    public function __construct(EmissionHandler $emissionHandler, ReceptionHandler $receptionHandler, ExplorationHandler $explorationHandler, LoggerInterface $logger)
    {
        $this->emissionHandler = $emissionHandler;
        $this->receptionHandler = $receptionHandler;
        $this->explorationHandler = $explorationHandler;
        $this->logger = $logger;
    }

    public function performJob(string $type, MappingComponentStructContract $mapping, ?array $payload): bool
    {
        switch ($type) {
            case Emission::class:
                return $this->emissionHandler->triggerEmission($mapping);
            case Reception::class:
                return $this->receptionHandler->triggerReception($mapping, $payload);
            case Exploration::class:
                return $this->explorationHandler->triggerExploration($mapping);
        }
        /*
         * Logger nicht correct injected, wirft error in der queue wenn erreicht
         */
        $this->logger.debug(\sprintf(
            'DelegatingJobActor: Unable to find correct Handler for type %s.',
            $type
        ));
        return true;
    }
}
