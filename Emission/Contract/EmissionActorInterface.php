<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

interface EmissionActorInterface
{
    /**
     * @param string[] $externalIds
     */
    public function performEmission(
        iterable $externalIds,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void;
}
