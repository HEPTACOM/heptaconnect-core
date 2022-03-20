<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionLoaderInterface;
use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionTokenContract;

final class InstructionFileLoader implements InstructionLoaderInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
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
            throw new \RuntimeException('No configuration file found in path ' . $this->path, 1645611612);
        }
    }
}
