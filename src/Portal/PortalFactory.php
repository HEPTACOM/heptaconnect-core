<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;

class PortalFactory implements Contract\PortalFactoryInterface
{
    public function instantiatePortalNode(string $class): PortalNodeInterface
    {
        if (!\class_exists($class)) {
            throw new Exception\ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalNodeInterface::class, true)) {
            throw new Exception\UnexpectedClassInheritanceOnInstantionException($class, PortalNodeInterface::class);
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

    public function instantiatePortalNodeExtension(string $class): PortalNodeExtensionInterface
    {
        if (!\class_exists($class)) {
            throw new Exception\ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalNodeExtensionInterface::class, true)) {
            throw new Exception\UnexpectedClassInheritanceOnInstantionException($class, PortalNodeExtensionInterface::class);
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
