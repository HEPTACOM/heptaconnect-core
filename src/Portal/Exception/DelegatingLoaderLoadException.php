<?php


namespace Heptacom\HeptaConnect\Core\Portal\Exception;


class DelegatingLoaderLoadException extends \Exception
{
    public function __construct(string $path, Throwable $previous = null)
    {
        parent::__construct(\sprintf('Exception when loading container service file from path %s', $path), 0, $previous);
    }
}
