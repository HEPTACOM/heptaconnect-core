<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive;

use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface;

class ReceiverRegistry implements Contract\ReceiverRegistryInterface
{
    /**
     * @var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface>
     */
    private array $receivers = [];

    /**
     * @var array<
     *             class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>,
     *             array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface>
     *             >|null
     */
    private ?array $bySupport;

    /**
     * @param iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface> $receivers
     * @psalm-param iterable<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface> $receivers
     */
    public function __construct(iterable $receivers)
    {
        foreach ($receivers as $key => $value) {
            $this->receivers[$key] = $value;
        }

        $this->bySupport = null;
    }

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClassName
     *
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface>
     */
    public function bySupport(string $datasetEntityClassName): array
    {
        if (\is_null($this->bySupport)) {
            $this->bySupport = [];

            /** @var ReceiverInterface $receiver */
            foreach ($this->receivers as $receiver) {
                foreach ($receiver->supports() as $className) {
                    $this->bySupport[$className] ??= [];
                    $this->bySupport[$className][] = $receiver;
                }
            }
        }

        return $this->bySupport[$datasetEntityClassName] ?? [];
    }
}
