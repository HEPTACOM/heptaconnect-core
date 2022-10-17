<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Psr\Log\LoggerInterface;

final class EmitterStack implements EmitterStackInterface
{
    private EmitterCollection $emitters;

    /**
     * @param iterable<EmitterContract> $emitters
     */
    public function __construct(iterable $emitters, private EntityType $entityType, private LoggerInterface $logger)
    {
        $this->emitters = new EmitterCollection($emitters);
    }

    public function next(iterable $externalIds, EmitContextInterface $context): iterable
    {
        $emitter = $this->emitters->shift();

        if (!$emitter instanceof EmitterContract) {
            return [];
        }

        $this->logger->debug('Execute FlowComponent emitter', [
            'emitter' => $emitter,
        ]);

        return $emitter->emit($externalIds, $context, $this);
    }

    public function supports(): EntityType
    {
        return $this->entityType;
    }
}
