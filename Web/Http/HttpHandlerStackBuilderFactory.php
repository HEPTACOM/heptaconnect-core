<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Web\Http;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Web\Http\Contract\HttpHandlerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Psr\Log\LoggerInterface;

class HttpHandlerStackBuilderFactory implements HttpHandlerStackBuilderFactoryInterface
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
        $container = $this->portalContainerFactory->create($portalNodeKey);
        /** @var HttpHandlerCollection $sources */
        $sources = $container->get(HttpHandlerCollection::class);
        /** @var HttpHandlerCollection $decorators */
        $decorators = $container->get(HttpHandlerCollection::class . '.decorator');

        return new HttpHandlerStackBuilder($sources, $decorators, $path, $this->logger);
    }
}
