<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\StatusReporting\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface StatusReportingServiceInterface
{
    public function report(PortalNodeKeyInterface $portalNodeKey, ?string $topic): array;
}
