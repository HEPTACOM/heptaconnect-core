<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission\Contract;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;

interface EmitterStackProcessorInterface
{
    /**
     * Passes the external ids through the stack and aggregates the result.
     *
     * @param string[] $externalIds
     */
    public function processStack(
        iterable $externalIds,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): TypedDatasetEntityCollection;
}
