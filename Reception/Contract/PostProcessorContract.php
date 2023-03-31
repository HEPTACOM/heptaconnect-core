<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\Contract;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class PostProcessorContract implements EventSubscriberInterface
{
    abstract public function handle(PostReceptionEvent $event): void;

    public static function getSubscribedEvents(): array
    {
        return [
            PostReceptionEvent::class => 'handle',
        ];
    }
}
