<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

interface EmitterRegistryInterface
{
    /**
     * @param class-string<DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, EmitterInterface>
     */
    public function bySupport(string $datasetEntityClassName): array;
}
