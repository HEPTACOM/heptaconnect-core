<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

class ThrowReceiver extends ReceiverContract
{
    public function receive(
        MappedDatasetEntityCollection $mappedDatasetEntities,
        ReceiveContextInterface $context,
        ReceiverStackInterface $stack
    ): iterable {
        throw new \RuntimeException();
    }

    public function supports(): array
    {
        return [
            FooBarEntity::class,
        ];
    }
}
