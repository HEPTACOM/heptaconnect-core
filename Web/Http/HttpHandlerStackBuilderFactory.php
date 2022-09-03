<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Psr\Log\LoggerInterface;

final class HttpHandlerStackBuilderFactory implements HttpHandlerStackBuilderFactoryInterface
{
    private PortalStackServiceContainerFactory $portalContainerFactory;

    private LoggerInterface $logger;

    public function __construct(PortalStackServiceContainerFactory $portalContainerFactory, LoggerInterface $logger)
    {
        $this->portalContainerFactory = $portalContainerFactory;
        $this->logger = $logger;
    }

    public function createHttpHandlerStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $path
    ): HttpHandlerStackBuilderInterface {
        $flowComponentRegistry = $this->portalContainerFactory
            ->create($portalNodeKey)
            ->getFlowComponentRegistry();
        $components = new HttpHandlerCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getWebHttpHandlers($source));
        }

        return new HttpHandlerStackBuilder($components, $path, $this->logger);
    }
}
