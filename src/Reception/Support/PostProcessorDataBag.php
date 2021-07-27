<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Support;

class PostProcessorDataBag
{
    private array $items = [];

    public function add(object $postProcessorData, int $priority = 0): void
    {
        $this->items[$priority][] = $postProcessorData;
    }

    public function remove(object $postProcessorData)
    {
        foreach ($this->items as $priority => $items) {
            foreach ($items as $key => $item) {
                if ($item === $postProcessorData) {
                    unset($this->items[$priority][$key]);

                    break;
                }
            }
        }
    }

    public function of(string $className): iterable
    {
        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if (\is_a($item, $className)) {
                    yield $item;
                }
            }
        }
    }
}
