<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;

class PortalFactory implements Contract\PortalFactoryInterface
{
    public function instantiatePortal(string $class): PortalInterface
    {
        if (!\class_exists($class)) {
            throw new Exception\ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalInterface::class, true)) {
            throw new Exception\UnexpectedClassInheritanceOnInstantionException($class, PortalInterface::class);
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception\InaccessableConstructorOnInstantionException($class);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new Exception\UnexpectedRequiredParameterInConstructorOnInstantionException($class);
        }

        return new $class();
    }

    public function instantiatePortalExtension(string $class): PortalExtensionInterface
    {
        if (!\class_exists($class)) {
            throw new Exception\ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalExtensionInterface::class, true)) {
            throw new Exception\UnexpectedClassInheritanceOnInstantionException($class, PortalExtensionInterface::class);
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception\InaccessableConstructorOnInstantionException($class);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new Exception\UnexpectedRequiredParameterInConstructorOnInstantionException($class);
        }

        return new $class();
    }
}
