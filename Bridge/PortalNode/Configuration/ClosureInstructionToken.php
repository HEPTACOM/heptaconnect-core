<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;

final class ClosureInstructionToken extends InstructionTokenContract
{
    /**
     * @var \Closure(\Closure(): array): array
     */
    private \Closure $closure;

    /**
     * @param class-string<PortalContract>|class-string<PortalExtensionContract>|class-string|string $query
     * @param \Closure(\Closure(): array): array                                                     $closure
     */
    public function __construct(string $query, \Closure $closure)
    {
        parent::__construct($query);
        $this->closure = $closure;
    }

    /**
     * @return \Closure(\Closure(): array): array
     */
    public function getClosure(): \Closure
    {
        return $this->closure;
    }
}
