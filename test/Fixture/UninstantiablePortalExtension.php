<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;

abstract class UninstantiablePortalExtension extends PortalExtensionContract
{
    public function getExplorerDecorators(): ExplorerCollection
    {
        return new ExplorerCollection();
    }

    public function getEmitterDecorators(): EmitterCollection
    {
        return new EmitterCollection();
    }

    public function getReceiverDecorators(): ReceiverCollection
    {
        return new ReceiverCollection();
    }

    public function supports(): string
    {
        return UninstantiablePortal::class;
    }
}
