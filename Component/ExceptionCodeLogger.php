<?php

namespace Heptacom\HeptaConnect\Core\Component;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class ExceptionCodeLogger extends AbstractLogger
{
    private LoggerInterface $decorated;

    public function __construct(LoggerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function log($level, $message, array $context = [])
    {
        $codeMessage = '';
        if (!empty($context)) {
            $exception = $context[0];
            if ($exception instanceof \Exception) {
                $codeMessage = '['.\get_class($exception).'Code: '.$exception->getCode().'] ';
            }
        }
        $this->decorated->log($level, $codeMessage.$message, $context);
    }
}
