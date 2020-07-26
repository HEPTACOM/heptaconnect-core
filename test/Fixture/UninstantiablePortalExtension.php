<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Test\Fixture;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\ReceiverCollection;

abstract class UninstantiablePortalExtension implements PortalNodeExtensionInterface
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
