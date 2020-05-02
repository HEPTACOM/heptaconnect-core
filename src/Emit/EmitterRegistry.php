<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;

class EmitterRegistry implements EmitterRegistryInterface
{
    /**
     * @var array<array-key, EmitterInterface>
     */
    private array $emitters;

    /**
     * @var array<class-string<DatasetEntityInterface>, array<array-key, EmitterInterface>>|null
     */
    private ?array $bySupport;

    /**
     * @param iterable<array-key, EmitterInterface> $emitters
     * @psalm-param iterable<array-key, EmitterInterface> $emitters
     */
    public function __construct(iterable $emitters)
    {
        $this->emitters = [...$emitters];
    }

    /**
     * @param class-string<DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, EmitterInterface>
     */
    public function bySupport(string $datasetEntityClassName): array
    {
        if (\is_null($this->bySupport)) {
            $this->bySupport = [];

            /** @var EmitterInterface $emit */
            foreach ($this->emitters as $emit) {
                /* @noinspection SuspiciousLoopInspection */
                foreach ($emit->supports() as $datasetEntityClassName) {
                    $this->bySupport[$datasetEntityClassName] ??= [];
                    $this->bySupport[$datasetEntityClassName][] = $emit;
                }
            }
        }

        return $this->bySupport[$datasetEntityClassName] ?? [];
    }
}
