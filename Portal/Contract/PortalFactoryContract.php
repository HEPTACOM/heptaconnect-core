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

abstract class PortalFactoryContract
{
    /**
     * @throws AbstractInstantiationException
     */
    public function instantiatePortal(PortalType $class): PortalContract
    {
        return $this->instantiateObject((string) $class);
    }

    /**
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalExtension(PortalExtensionType $class): PortalExtensionContract
    {
        return $this->instantiateObject((string) $class);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @throws AbstractInstantiationException
     *
     * @return T
     */
    private function instantiateObject(string $class): object
    {
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
