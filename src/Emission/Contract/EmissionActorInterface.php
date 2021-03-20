<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;

interface EmissionActorInterface
{
    public function performEmission(
        TypedMappingCollection $mappings,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void;
}
