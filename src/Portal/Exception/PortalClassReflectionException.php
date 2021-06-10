<?php


namespace Heptacom\HeptaConnect\Core\Portal\Exception;


class PortalClassReflectionException extends \ReflectionException
{
    public function __construct(string $className, Throwable $previous = null)
    {
        parent::__construct(\sprintf('Reflection of Portalclass not successfull with specified name %s', $className), 0, $previous);
    }

}
