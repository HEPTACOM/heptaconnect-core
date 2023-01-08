<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Core\Portal\Exception\InaccessableConstructorOnInstantionException;
use Heptacom\HeptaConnect\Core\Portal\Exception\UnexpectedRequiredParameterInConstructorOnInstantionException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalType;

/**
 * Factory service to instantiate main objects of packages like @see PortalContract, PortalExtensionContract
 */
abstract class PortalFactoryContract
{
    /**
     * Create a new instance of the given @see PortalType
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortal(PortalType $class): PortalContract
    {
        $classString = (string) $class;
        $reflection = new \ReflectionClass($classString);

        if (!$reflection->isInstantiable()) {
            throw new InaccessableConstructorOnInstantionException($classString);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new UnexpectedRequiredParameterInConstructorOnInstantionException($classString);
        }

        return new $classString();
    }

    /**
     * Create a new instance of the given @see PortalExtensionType
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalExtension(PortalExtensionType $class): PortalExtensionContract
    {
        $classString = (string) $class;
        $reflection = new \ReflectionClass($classString);

        if (!$reflection->isInstantiable()) {
            throw new InaccessableConstructorOnInstantionException($classString);
        }

        $ctor = $reflection->getConstructor();

        if ($ctor instanceof \ReflectionMethod && $ctor->getNumberOfRequiredParameters() > 0) {
            throw new UnexpectedRequiredParameterInConstructorOnInstantionException($classString);
        }

        return new $classString();
    }
}
