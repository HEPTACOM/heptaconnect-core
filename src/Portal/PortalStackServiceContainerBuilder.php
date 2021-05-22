<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Core\Storage\NormalizationRegistry;
use Heptacom\HeptaConnect\Portal\Base\Parallelization\Support\ResourceLockFacade;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalNodeContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Psr\Log\LoggerInterface;

class PortalStackServiceContainerBuilder
{
    private LoggerInterface $logger;

    private NormalizationRegistry $normalizationRegistry;

    public function __construct(LoggerInterface $logger, NormalizationRegistry $normalizationRegistry)
    {
        $this->logger = $logger;
        $this->normalizationRegistry = $normalizationRegistry;
    }

    public function build(
        PortalContract $portal,
        PortalExtensionCollection $portalExtensions,
        PortalNodeContextInterface $context
    ): PortalStackServiceContainer {
        $services = $portal->getServices() + [
            'portal' => $portal,
            \get_class($portal) => $portal,
            PortalNodeContextInterface::class => $context,
            PortalStorageInterface::class => $context->getStorage(),
            ResourceLockFacade::class => $context->getResourceLocker(),
        ];

        /** @var PortalExtensionContract $portalExtension */
        foreach ($portalExtensions as $portalExtension) {
            $services = $portalExtension->extendServices($services);

            $services['portal_extensions'][] = $portalExtension;
            $services[\get_class($portalExtension)] = $portalExtension;
        }

        $services[DeepCloneContract::class] ??= new DeepCloneContract();
        $services[DeepObjectIteratorContract::class] ??= new DeepObjectIteratorContract();
        $services[LoggerInterface::class] ??= $this->logger;
        $services[NormalizationRegistry::class] ??= $this->normalizationRegistry;

        return new PortalStackServiceContainer($services);
    }
}
