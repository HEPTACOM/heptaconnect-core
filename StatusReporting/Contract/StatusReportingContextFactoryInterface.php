<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting\Contract;

use Heptacom\HeptaConnect\Portal\Base\StatusReporting\Contract\StatusReportingContextInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface StatusReportingContextFactoryInterface
{
    /**
     * Create a context for a status report on the given portal node.
     */
    public function factory(PortalNodeKeyInterface $portalNodeKey): StatusReportingContextInterface;
}
