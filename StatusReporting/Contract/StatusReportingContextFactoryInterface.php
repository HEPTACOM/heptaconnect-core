<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting\Contract;

use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface StatusReportingContextFactoryInterface
{
    public function factory(PortalNodeKeyInterface $portalNodeKey): StatusReportingContextInterface;
}
