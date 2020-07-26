<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\ReceiverCollection;

class DependentPortal implements PortalNodeInterface
{
    private ExplorerCollection $explorerCollection;

    public function __construct(ExplorerCollection $explorerCollection)
    {
        $this->explorerCollection = $explorerCollection;
    }

    public function getExplorers(): ExplorerCollection
    {
        return $this->explorerCollection;
    }

    public function getEmitters(): EmitterCollection
    {
        return new EmitterCollection();
    }

    public function getReceivers(): ReceiverCollection
    {
        return new ReceiverCollection();
    }
}
