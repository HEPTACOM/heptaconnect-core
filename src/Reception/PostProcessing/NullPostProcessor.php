<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;

class NullPostProcessor extends PostProcessorContract
{
    public function handle(PostReceptionEvent $event): void
    {
        $entities = \iterable_map(
            $event->getContext()->getPostProcessingBag()->of(NullPostProcessorData::class),
            static fn (NullPostProcessorData $data) => $data->getEntity()
        );
    }
}
