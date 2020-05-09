<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;

class FooBarReceiver implements ReceiverInterface
{
    public function receive(
        MappedDatasetEntityCollection $mappedDatasetEntities,
        ReceiveContextInterface $context
    ): iterable {
        /** @var MappedDatasetEntityStruct $mappedDatasetEntity */
        foreach ($mappedDatasetEntities as $mappedDatasetEntity) {
            $mapping = $mappedDatasetEntity->getMapping();
            $mapping->setExternalId('');

            yield $mapping;
        }
    }

    public function supports(): array
    {
        return [
            FooBarEntity::class,
        ];
    }
}
