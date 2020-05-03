<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive\Contract;

interface ReceiverRegistryInterface
{
    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface>
     */
    public function bySupport(string $datasetEntityClassName): array;
}
