<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class PortalLogger extends AbstractLogger
{
    private LoggerInterface $decorated;

    private string $prefix;

    private array $context;

    public function __construct(LoggerInterface $decorated, string $prefix, array $context)
    {
        $this->decorated = $decorated;
        $this->prefix = $prefix;
        $this->context = $context;
    }

    public function log($level, $message, array $context = [])
    {
        $this->decorated->log($level, $this->prefix.$message, \array_merge($context, $this->context));
    }
}
