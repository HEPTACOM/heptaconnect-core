<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;

abstract class InstructionTokenContract
{
    /**
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     */
    public function __construct(
        private string $query
    ) {
    }

    /**
     * Get the query to match a portal node against.
     * It can contain class names that belong to portals, portal extensions or their text representation.
     *
     * @return class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
