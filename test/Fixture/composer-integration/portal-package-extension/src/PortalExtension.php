<?php declare(strict_types=1);

namespace HeptacomFixture\Portal\Extension;

use Heptacom\HeptaConnect\Portal\Base\Support\AbstractPortalExtension;

class PortalExtension extends AbstractPortalExtension
{
    public function supports(): string
    {
        return 'HeptacomFixture\Portal\A\Portal';
    }
}
