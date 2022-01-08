<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Core\Portal\Exception\ClassNotFoundOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\InaccessableConstructorOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedClassInheritanceOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedRequiredParameterInConstructorOnInstantionException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;

abstract class PortalFactoryContract
{
    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortal(string $class): PortalContract
    {
        if (!\class_exists($class)) {
            throw new ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalContract::class, true)) {
            throw new UnexpectedClassInheritanceOnInstantionException($class, PortalContract::class);
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new InaccessableConstructorOnInstantionException($class);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new UnexpectedRequiredParameterInConstructorOnInstantionException($class);
        }

        return new $class();
    }

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalExtension(string $class): PortalExtensionContract
    {
        if (!\class_exists($class)) {
            throw new ClassNotFoundOnInstantionException($class);
        }

        if (!\is_a($class, PortalExtensionContract::class, true)) {
            throw new UnexpectedClassInheritanceOnInstantionException($class, PortalExtensionContract::class);
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new InaccessableConstructorOnInstantionException($class);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new UnexpectedRequiredParameterInConstructorOnInstantionException($class);
        }

        return new $class();
    }
}
