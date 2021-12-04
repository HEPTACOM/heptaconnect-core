<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;

class FooBarReceiver extends ReceiverContract
{
    public function receive(
        TypedDatasetEntityCollection $entities,
        ReceiveContextInterface $context,
        ReceiverStackInterface $stack
    ): iterable {
        /** @var DatasetEntityContract $entity */
        foreach ($entities as $entity) {
            $entity->setPrimaryKey('');

            yield $entity;
        }

        yield from $stack->next($mappedDatasetEntities, $context);
    }

    public function supports(): string
    {
        return FooBarEntity::class;
    }
}
