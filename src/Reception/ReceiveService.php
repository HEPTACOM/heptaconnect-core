<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class ReceiveService implements ReceiveServiceInterface
{
    private ReceiveContextInterface $receiveContext;

    private LoggerInterface $logger;

    private PortalRegistryInterface $portalRegistry;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $receiverStackCache = [];

    public function __construct(
        ReceiveContextInterface $receiveContext,
        LoggerInterface $logger,
        PortalRegistryInterface $portalRegistry,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->receiveContext = $receiveContext;
        $this->logger = $logger;
        $this->portalRegistry = $portalRegistry;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function receive(TypedMappedDatasetEntityCollection $mappedDatasetEntities, callable $saveMappings): void
    {
        $receivingPortalNodes = [];
        $entityClassName = $mappedDatasetEntities->getType();

        /** @var MappedDatasetEntityStruct $mappedDatasetEntity */
        foreach ($mappedDatasetEntities as $mappedDatasetEntity) {
            $portalNodeKey = $mappedDatasetEntity->getMapping()->getPortalNodeKey();

            if (\array_reduce($receivingPortalNodes, fn (bool $match, PortalNodeKeyInterface $key) => $match || $key->equals($portalNodeKey), false)) {
                continue;
            }

            $receivingPortalNodes[] = $portalNodeKey;
            $mappedDatasetEntitiesIterator = $mappedDatasetEntities->filter(
                fn (MappedDatasetEntityStruct $mappedDatasetEntityStruct) => $mappedDatasetEntityStruct->getMapping()->getPortalNodeKey()->equals($portalNodeKey)
            );
            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct> $mappedDatasetEntitiesForPortalNode */
            $mappedDatasetEntitiesForPortalNode = iterable_to_array($mappedDatasetEntitiesIterator);
            $mappedDatasetEntitiesForPortalNode = new TypedMappedDatasetEntityCollection(
                $entityClassName,
                $mappedDatasetEntitiesForPortalNode
            );

            try {
                $stacks = $this->getReceiverStacks($portalNodeKey, $entityClassName);
            } catch (\Throwable $exception) {
                $this->logger->critical(LogMessage::RECEIVE_NO_STACKS(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                    'exception' => $exception,
                ]);

                continue;
            }

            if (empty($stacks)) {
                $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

            /** @var ReceiverStackInterface $stack */
            foreach ($stacks as $stack) {
                try {
                    /** @var MappingInterface $mapping */
                    foreach ($stack->next($mappedDatasetEntitiesForPortalNode, $this->receiveContext) as $mapping) {
                        $saveMappings($mapping->getPortalNodeKey());
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::RECEIVE_NO_THROW(), [
                        'type' => $entityClassName,
                        'portalNodeKey' => $portalNodeKey,
                        'stack' => $stack,
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }

    /**
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface>
     */
    private function getReceiverStacks(PortalNodeKeyInterface $portalNodeKey, string $entityClassName): array
    {
        $cacheKey = \md5(\join([$this->storageKeyGenerator->serialize($portalNodeKey), $entityClassName]));

        if (!isset($this->receiverStackCache[$cacheKey])) {
            $portal = $this->portalRegistry->getPortal($portalNodeKey);
            $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);
            $receivers = iterable_to_array($portal->getReceivers()->bySupport($entityClassName));
            $receiverDecorators = iterable_to_array($portalExtensions->getReceiverDecorators()->bySupport($entityClassName));

            if ($receivers) {
                foreach ($receivers as $receiver) {
                    $this->receiverStackCache[$cacheKey][] = new ReceiverStack([...$receiverDecorators, $receiver]);
                }
            } elseif ($receiverDecorators) {
                $this->receiverStackCache[$cacheKey][] = new ReceiverStack([...$receiverDecorators]);
            }
        }

        return \array_map(
            fn (ReceiverStackInterface $receiverStack) => clone $receiverStack,
            $this->receiverStackCache[$cacheKey] ??= []
        );
    }
}
