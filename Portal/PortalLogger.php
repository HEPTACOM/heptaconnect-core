<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class PortalLogger extends AbstractLogger
{
    public function __construct(
        private readonly LoggerInterface $decorated,
        private readonly string $prefix,
        private readonly array $context
    ) {
    }

    #[\Override]
    public function log($level, $message, array $context = []): void
    {
        $this->decorated->log($level, $this->prefix . $message, \array_merge($context, $this->context));
    }
}
