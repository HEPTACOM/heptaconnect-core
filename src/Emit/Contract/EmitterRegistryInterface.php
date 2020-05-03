<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit\Contract;

interface EmitterRegistryInterface
{
    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface>
     */
    public function bySupport(string $datasetEntityClassName): array;
}
