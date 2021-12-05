<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Exception;

use RuntimeException;
use Throwable;

class GzipCompressException extends RuntimeException
{
    public function __construct(int $code, ?Throwable $throwable = null)
    {
        parent::__construct('', $code, $throwable);
    }
}
