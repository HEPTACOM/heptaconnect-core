<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;

/**
 * @extends DatasetEntityCollection<DatasetEntityInterface>
 */
class EmitResult extends DatasetEntityCollection
{
    protected function getT(): string
    {
        return DatasetEntityInterface::class;
    }
}
