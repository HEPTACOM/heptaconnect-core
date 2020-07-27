<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;

interface PortalFactoryInterface
{
    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortal(string $class): PortalInterface;

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionInterface> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalExtension(string $class): PortalExtensionInterface;
}
