<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
class ServiceNotInstantiableEndlessLoopDetected extends \Exception
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Only callables have been returned', 0, $previous);
    }
}
