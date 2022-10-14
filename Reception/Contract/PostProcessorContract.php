<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Portal\Base\Reception\Support\PostProcessorDataBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class PostProcessorContract implements EventSubscriberInterface
{
    /**
     * Process the given event.
     * Altering the given @see PostProcessorDataBag is allowed and expected to affect behaviour reception stack processing.
     */
    abstract public function handle(PostReceptionEvent $event): void;

    public static function getSubscribedEvents(): array
    {
        return [
            PostReceptionEvent::class => 'handle',
        ];
    }
}
