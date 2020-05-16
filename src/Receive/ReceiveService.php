<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Receive;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Core\Receive\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\ReceiverInterface;
use Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\TypedMappedDatasetEntityCollection;
use Psr\Log\LoggerInterface;

class ReceiveService implements ReceiveServiceInterface
{
    private MappingServiceInterface $mappingService;

    private ReceiveContextInterface $receiveContext;

    private LoggerInterface $logger;

    private PortalNodeRegistryInterface $portalNodeRegistry;

    public function __construct(
        MappingServiceInterface $mappingService,
        ReceiveContextInterface $receiveContext,
        LoggerInterface $logger,
        PortalNodeRegistryInterface $portalNodeRegistry
    ) {
        $this->mappingService = $mappingService;
        $this->receiveContext = $receiveContext;
        $this->logger = $logger;
        $this->portalNodeRegistry = $portalNodeRegistry;
    }

    public function receive(TypedMappedDatasetEntityCollection $mappedDatasetEntities): void
    {
        $receivingPortalNodes = [];
        $entityClassName = $mappedDatasetEntities->getType();

        /** @var MappedDatasetEntityStruct $mappedDatasetEntity */
        foreach ($mappedDatasetEntities as $mappedDatasetEntity) {
            $portalNodeId = $mappedDatasetEntity->getMapping()->getPortalNodeId();

            if (isset($receivingPortalNodes[$portalNodeId])) {
                continue;
            }

            $portalNode = $this->portalNodeRegistry->getPortalNode($portalNodeId);
            if (!$portalNode instanceof PortalNodeInterface) {
                continue;
            }

            $receivingPortalNodes[$portalNodeId] = $portalNode->getReceivers()->bySupport($entityClassName);
        }

        foreach ($receivingPortalNodes as $portalNodeId => $receivers) {
            $mappedDatasetEntitiesIterator = $mappedDatasetEntities->filter(function (MappedDatasetEntityStruct $mappedDatasetEntityStruct) use ($portalNodeId): bool {
                return $mappedDatasetEntityStruct->getMapping()->getPortalNodeId() === $portalNodeId;
            });

            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\MappedDatasetEntityStruct> $mappedDatasetEntitiesForPortalNode */
            $mappedDatasetEntitiesForPortalNode = \iterator_to_array($mappedDatasetEntitiesIterator);
            $mappedDatasetEntitiesForPortalNode = new TypedMappedDatasetEntityCollection(
                $entityClassName,
                $mappedDatasetEntitiesForPortalNode
            );

            $hasReceivers = false;

            /** @var ReceiverInterface $receiver */
            foreach ($receivers as $receiver) {
                $hasReceivers = true;

                try {
                    foreach ($receiver->receive($mappedDatasetEntitiesForPortalNode, $this->receiveContext) as $mapping) {
                        $this->mappingService->save($mapping);
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::RECEIVE_NO_THROW(), [
                        'type' => $entityClassName,
                        'receiver' => \get_class($receiver),
                        'exception' => $exception,
                    ]);
                }
            }

            if (!$hasReceivers) {
                $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeId' => $portalNodeId,
                ]);
            }
        }
    }
}
