<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class EmitterStackBuilderFactory implements EmitterStackBuilderFactoryInterface
{
    private PortalStackServiceContainerFactory $portalContainerFactory;

    private LoggerInterface $logger;

    public function __construct(PortalStackServiceContainerFactory $portalContainerFactory, LoggerInterface $logger)
    {
        $this->portalContainerFactory = $portalContainerFactory;
        $this->logger = $logger;
    }

    public function createEmitterStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityType
    ): EmitterStackBuilderInterface {
        $container = $this->portalContainerFactory->create($portalNodeKey);
        /** @var EmitterCollection $sources */
        $sources = $container->get(EmitterCollection::class);
        /** @var EmitterCollection $decorators */
        $decorators = $container->get(EmitterCollection::class . '.decorator');

        return new EmitterStackBuilder($sources, $decorators, $entityType, $this->logger);
    }
}
