<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration;

use Heptacom\HeptaConnect\Core\Bridge\PortalNode\Configuration\Contract\InstructionLoaderInterface;

final readonly class InstructionFileLoader implements InstructionLoaderInterface
{
    public function __construct(
        private string $path
    ) {
    }

    #[\Override]
    public function loadInstructions(): InstructionTokenCollection
    {
        try {
            $config = new Config();

            if (!\file_exists($this->path)) {
                throw new \RuntimeException(\sprintf('File %s does not exist', $this->path), 1717858650);
            }

            require $this->path;

            return $config->buildInstructions();
        } catch (\Throwable $throwable) {
            throw new \RuntimeException('Can not load configuration file ' . $this->path, 1645611612, $throwable);
        }
    }
}
