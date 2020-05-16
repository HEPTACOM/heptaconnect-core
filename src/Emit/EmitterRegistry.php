<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;

class EmitterRegistry implements EmitterRegistryInterface
{
    /**
     * @var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface>
     */
    private array $emitters = [];

    /**
     * @var array<
     *             class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>,
     *             array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface>
     *             >|null
     */
    private ?array $bySupport;

    /**
     * @param iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface> $emitters
     * @psalm-param iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface> $emitters
     */
    public function __construct(iterable $emitters)
    {
        foreach ($emitters as $key => $value) {
            $this->emitters[$key] = $value;
        }

        $this->bySupport = null;
    }

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface>
     */
    public function bySupport(string $datasetEntityClassName): array
    {
        if (\is_null($this->bySupport)) {
            $this->bySupport = [];

            /** @var EmitterInterface $emit */
            foreach ($this->emitters as $emit) {
                foreach ($emit->supports() as $className) {
                    $this->bySupport[$className] ??= [];
                    $this->bySupport[$className][] = $emit;
                }
            }
        }

        return $this->bySupport[$datasetEntityClassName] ?? [];
    }
}
