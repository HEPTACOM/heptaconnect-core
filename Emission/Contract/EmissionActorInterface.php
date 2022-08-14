<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

interface EmissionActorInterface
{
    /**
     * Perform an emission for the given ids on the given stack.
     *
     * @param string[] $externalIds
     */
    public function performEmission(
        iterable $externalIds,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void;
}
