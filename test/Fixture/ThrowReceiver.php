<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

class ThrowReceiver extends ReceiverContract
{
    public function receive(
        TypedDatasetEntityCollection $entities,
        ReceiveContextInterface $context,
        ReceiverStackInterface $stack
    ): iterable {
        throw new \RuntimeException();
    }

    public function supports(): string
    {
        return FooBarEntity::class;
    }
}
