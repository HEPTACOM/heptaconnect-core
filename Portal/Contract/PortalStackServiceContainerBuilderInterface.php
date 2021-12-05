<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\Contract;

use Heptacom\HeptaConnect\Core\Portal\Exception\DelegatingLoaderLoadException;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface PortalStackServiceContainerBuilderInterface
{
    /**
     * @throws DelegatingLoaderLoadException
     */
    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeKeyInterface $portalNodeKey
    ): ContainerBuilder;
}
