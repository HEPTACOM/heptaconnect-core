<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

interface ReceptionActorInterface
{
    public function performReception(
        TypedDatasetEntityCollection $entities,
        ReceiverStackInterface $stack,
        ReceiveContextInterface $context
    ): void;
}
