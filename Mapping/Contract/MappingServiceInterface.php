<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface MappingServiceInterface
{
    public function addException(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        \Throwable $exception
    ): void;
}
