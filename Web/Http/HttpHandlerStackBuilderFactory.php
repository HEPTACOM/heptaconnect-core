<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerStackIdentifier;
use Psr\Log\LoggerInterface;

final class HttpHandlerStackBuilderFactory implements HttpHandlerStackBuilderFactoryInterface
{
    public function __construct(
        private PortalStackServiceContainerFactory $portalContainerFactory,
        private LoggerInterface $logger
    ) {
    }

    public function createHttpHandlerStackBuilder(HttpHandlerStackIdentifier $stackIdentifier): HttpHandlerStackBuilderInterface
    {
        $flowComponentRegistry = $this->portalContainerFactory
            ->create($stackIdentifier->getPortalNodeKey())
            ->getFlowComponentRegistry();
        $components = new HttpHandlerCollection();

        foreach ($flowComponentRegistry->getOrderedSources() as $source) {
            $components->push($flowComponentRegistry->getWebHttpHandlers($source));
        }

        return new HttpHandlerStackBuilder($components, $stackIdentifier->getPath(), $this->logger);
    }
}
