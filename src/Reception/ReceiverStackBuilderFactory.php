<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;

class ReceiverStackBuilderFactory implements ReceiverStackBuilderFactoryInterface
{
    private PortalStackServiceContainerFactory $portalContainerFactory;

    private LoggerInterface $logger;

    public function __construct(PortalStackServiceContainerFactory $portalContainerFactory, LoggerInterface $logger)
    {
        $this->portalContainerFactory = $portalContainerFactory;
        $this->logger = $logger;
    }

    public function createReceiverStackBuilder(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ): ReceiverStackBuilderInterface {
        $container = $this->portalContainerFactory->create($portalNodeKey);
        /** @var ReceiverCollection $receivers */
        $receivers = $container->get(ReceiverCollection::class);
        /** @var ReceiverCollection $receiverDecorators */
        $receiverDecorators = $container->get(ReceiverCollection::class.'.decorator');
        $receivers->push($receiverDecorators);

        return new ReceiverStackBuilder($receivers, $receiverDecorators, $entityClassName, $this->logger);
    }
}
