<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

use Psr\SimpleCache\CacheException;

class PortalStorageExceptionWrapper extends \Exception implements CacheException
{
    public function __construct(string $method, ?\Throwable $previous = null)
    {
        parent::__construct('The cache failed in '.$method, 1631375161, $previous);
    }
}
