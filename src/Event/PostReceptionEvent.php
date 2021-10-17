<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Event;

use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PostReceptionEvent extends Event
{
    private ReceiveContextInterface $context;

    public function __construct(ReceiveContextInterface $context)
    {
        $this->context = $context;
    }

    public function getContext(): ReceiveContextInterface
    {
        return $this->context;
    }
}
