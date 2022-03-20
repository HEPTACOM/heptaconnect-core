<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Configuration;

use Psr\Log\LoggerInterface;

class ConfigurationFileReader
{
    private LoggerInterface $logger;

    private string $path;

    private array $alias;

    public function __construct(LoggerInterface $logger, string $path, array $alias)
    {
        $this->logger = $logger;
        $this->path = $path;
        $this->alias = $alias;
    }

    public function callConfigurationScript($alias, $storedConfig): array
    {
        try {
            return (include $this->path)($alias, $storedConfig);
        } catch (\Throwable $throwable) {
            $error = new \Error('No configuration file found in path ' . $this->path, 1645611612);
            $this->logger->critical('', [
                'exception' => $error,
                'path' => $this->path,
            ]);

            throw $error;
        }
    }

    public function getAlias(): array
    {
        return $this->alias;
    }
}
