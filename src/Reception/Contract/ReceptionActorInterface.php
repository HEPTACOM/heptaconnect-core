<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

interface ReceptionActorInterface
{
    public function performReception(
        TypedMappedDatasetEntityCollection $mappedDatasetEntities,
        ReceiverStackInterface $stack,
        ReceiveContextInterface $context
    ): void;
}
