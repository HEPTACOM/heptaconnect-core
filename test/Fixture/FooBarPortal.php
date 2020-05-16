<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\ReceiverCollection;

class FooBarPortal implements PortalNodeInterface
{
    public function getExplorers(): ExplorerCollection
    {
        return new ExplorerCollection();
    }

    public function getEmitters(): EmitterCollection
    {
        return new EmitterCollection([
            new FooBarEmitter(10),
        ]);
    }

    public function getReceivers(): ReceiverCollection
    {
        return new ReceiverCollection([
            new FooBarReceiver(),
        ]);
    }
}
