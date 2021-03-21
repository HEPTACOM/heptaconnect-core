<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiveServiceInterface;
use Heptacom\HeptaConnect\Core\Router\CumulativeMappingException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class ReceiveService implements ReceiveServiceInterface
{
    private ReceiveContextInterface $receiveContext;

    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $receiverStackCache = [];

    private ReceiverStackBuilderFactoryInterface $receiverStackBuilderFactory;

    public function __construct(
        ReceiveContextInterface $receiveContext,
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ReceiverStackBuilderFactoryInterface $receiverStackBuilderFactory
    ) {
        $this->receiveContext = $receiveContext;
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->receiverStackBuilderFactory = $receiverStackBuilderFactory;
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

            $stack = $this->getReceiverStack($portalNodeKey, $entityClassName);

            if (!$stack instanceof ReceiverStackInterface) {
                $this->logger->critical(LogMessage::RECEIVE_NO_RECEIVER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

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

                if ($exception instanceof CumulativeMappingException) {
                    foreach ($exception->getExceptions() as $innerException) {
                        $errorContext = [];

                        if ($innerException instanceof MappingNodeAreUnmergableException) {
                            $errorContext = [
                                'fromNode' => $innerException->getFrom(),
                                'intoNode' => $innerException->getInto(),
                            ];
                        }

                        $this->logger->critical(LogMessage::RECEIVE_NO_THROW().'_INNER', [
                            'exception' => $innerException,
                        ] + $errorContext);
                    }
                }
            }
        }
    }

    private function getReceiverStack(PortalNodeKeyInterface $portalNodeKey, string $entityClassName): ?ReceiverStackInterface
    {
        $cacheKey = \join([$this->storageKeyGenerator->serialize($portalNodeKey), $entityClassName]);

        if (!\array_key_exists($cacheKey, $this->receiverStackCache)) {
            $builder = $this->receiverStackBuilderFactory
                ->createReceiverStackBuilder($portalNodeKey, $entityClassName)
                ->pushSource()
                // TODO break when source is already empty
                ->pushDecorators();

            $this->receiverStackCache[$cacheKey] = $builder->isEmpty() ? null : $builder->build();
        }

        $result = $this->receiverStackCache[$cacheKey];

        if ($result instanceof ReceiverStackInterface) {
            return clone $result;
        }

        return null;
    }
}
