<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionLoaderInterface;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;

final class InstructionFileLoader implements InstructionLoaderInterface
{
    public function __construct(
        private string $path
    ) {
    }

    /**
     * @return InstructionTokenContract[]
     */
    public function loadInstructions(): array
    {
        try {
            $config = new Config();
            require $this->path;

            return $config->buildInstructions();
        } catch (\Throwable $throwable) {
            throw new \RuntimeException('Can not load configuration file ' . $this->path, 1645611612, $throwable);
        }
    }
}
