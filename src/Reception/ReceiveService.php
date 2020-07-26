<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
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
            $portalNodeKey = $mappedDatasetEntity->getMapping()->getPortalNodeKey();

            if (\array_reduce($receivingPortalNodes, static function (bool $match, PortalNodeKeyInterface $key) use ($portalNodeKey): bool {
                return $match || $key->equals($portalNodeKey);
            }, false)) {
                continue;
            }

            $portalNode = $this->portalNodeRegistry->getPortalNode($portalNodeKey);
            if (!$portalNode instanceof PortalNodeInterface) {
                continue;
            }

            $portalExtensions = $this->portalNodeRegistry->getPortalNodeExtensions($portalNodeKey);
            $receivers = $portalNode->getReceivers()->bySupport($entityClassName);
            $receivingPortalNodes[] = $portalNodeKey;
            $mappedDatasetEntitiesIterator = $mappedDatasetEntities->filter(static function (MappedDatasetEntityStruct $mappedDatasetEntityStruct) use ($portalNodeKey): bool {
                return $mappedDatasetEntityStruct->getMapping()->getPortalNodeKey()->equals($portalNodeKey);
            });
            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct> $mappedDatasetEntitiesForPortalNode */
            $mappedDatasetEntitiesForPortalNode = iterable_to_array($mappedDatasetEntitiesIterator);
            $mappedDatasetEntitiesForPortalNode = new TypedMappedDatasetEntityCollection(
                $entityClassName,
                $mappedDatasetEntitiesForPortalNode
            );

            $hasReceivers = false;

            /** @var ReceiverInterface $receiver */
            foreach ($receivers as $receiver) {
                $hasReceivers = true;
                $stack = new ReceiverStack([
                    ...$portalExtensions->getReceiverDecorators()->bySupport($entityClassName),
                    $receiver,
                ]);

                try {
                    foreach ($stack->next($mappedDatasetEntitiesForPortalNode, $this->receiveContext) as $mapping) {
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
                    'portalNodeKey' => $portalNodeKey,
                ]);
            }
        }
    }
}
