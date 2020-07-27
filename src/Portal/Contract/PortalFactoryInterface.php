<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\Exception\AbstractInstantiationException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalInterface;

interface PortalFactoryInterface
{
    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeInterface> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalNode(string $class): PortalInterface;

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeExtensionInterface> $class
     *
     * @throws AbstractInstantiationException
     */
    public function instantiatePortalNodeExtension(string $class): PortalExtensionInterface;
}
