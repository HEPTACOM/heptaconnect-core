<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Exception;

/**
 * @deprecated Will be removed in version 0.10
 * Instead use @see \Heptacom\HeptaConnect\Portal\Base\Portal\Exception\DelegatingLoaderLoadException
 */
class DelegatingLoaderLoadException extends \Exception
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Exception when loading container service file from path %s', $path), 0, $previous);
    }
}
