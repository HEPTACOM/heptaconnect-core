<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Core\Router\CumulativeMappingException;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Log\LoggerInterface;

class ReceptionActor implements ReceptionActorInterface
{
    private LoggerInterface $logger;

    private MappingServiceInterface $mappingService;

    private DeepObjectIteratorContract $deepObjectIterator;

    public function __construct(
        LoggerInterface $logger,
        MappingServiceInterface $mappingService,
        DeepObjectIteratorContract $deepObjectIterator
    ) {
        $this->logger = $logger;
        $this->mappingService = $mappingService;
        $this->deepObjectIterator = $deepObjectIterator;
    }

    public function performReception(
        TypedMappedDatasetEntityCollection $mappedDatasetEntities,
        ReceiverStackInterface $stack,
        ReceiveContextInterface $context
    ): void {
        if ($mappedDatasetEntities->count() < 1) {
            return;
        }

        $entities = \array_map(
            static fn (MappedDatasetEntityStruct $m): DatasetEntityContract => $m->getDatasetEntity(),
            \iterable_to_array($mappedDatasetEntities)
        );

        foreach ($this->deepObjectIterator->iterate($entities) as $object) {
            if (!$object instanceof DatasetEntityContract) {
                continue;
            }

            $attachable = new PrimaryKeyChangesAttachable(\get_class($object));
            $attachable->setForeignKey($object->getPrimaryKey());
            $object->attach($attachable);
        }

        try {
            /** @var MappingInterface $mapping */
            foreach ($stack->next($mappedDatasetEntities, $context) as $mapping) {
                $this->saveMappings($context->getPortalNodeKey(), $entities);
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::RECEIVE_NO_THROW(), [
                'type' => $mappedDatasetEntities->getType(),
                'portalNodeKey' => $context->getPortalNodeKey(),
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

    /**
     * @param DatasetEntityContract[] $receivedEntityData
     */
    private function saveMappings(PortalNodeKeyInterface $targetPortalNodeKey, array $receivedEntityData): void
    {
        $exceptions = [];
        $originalReflectionMappingsByType = [];
        $keyChangesByType = [];

        foreach ($this->deepObjectIterator->iterate($receivedEntityData) as $receivedEntity) {
            if (!$receivedEntity instanceof DatasetEntityContract) {
                continue;
            }

            if ($receivedEntity->getPrimaryKey() === null) {
                continue;
            }

            $receivedEntityType = \get_class($receivedEntity);
            $primaryKeyChanges = $receivedEntity->getAttachment(PrimaryKeyChangesAttachable::class);

            if ($primaryKeyChanges instanceof PrimaryKeyChangesAttachable
                && !\is_null($primaryKeyChanges->getFirstForeignKey())
                && !\is_null($primaryKeyChanges->getForeignKey())
                && $primaryKeyChanges->getFirstForeignKey() !== $primaryKeyChanges->getForeignKey()) {
                $keyChangesByType[$receivedEntityType][$primaryKeyChanges->getFirstForeignKey()] = $primaryKeyChanges->getForeignKey();
            }

            $original = $receivedEntity->getAttachment(PrimaryKeySharingMappingStruct::class);

            if (!$original instanceof PrimaryKeySharingMappingStruct || $original->getExternalId() === null) {
                continue;
            }

            $originalReflectionMappingsByType[$receivedEntityType][$receivedEntity->getPrimaryKey()] = $original;
        }

        // TODO log these uncommon cases
        foreach ($keyChangesByType as $datasetEntityType => $keyChanges) {
            $oldMatchesIterable = $this->mappingService->getListByExternalIds(
                $datasetEntityType,
                $targetPortalNodeKey,
                \array_keys($keyChanges)
            );

            foreach ($oldMatchesIterable as $oldKey => $mapping) {
                $mapping->setExternalId($keyChanges[$oldKey]);
                $this->mappingService->save($mapping);
            }
        }

        // FIXME: something in this loop is terribly slow
        foreach ($originalReflectionMappingsByType as $datasetEntityType => $originalReflectionMappings) {
            $externalIds = \array_map('strval', \array_keys($originalReflectionMappings));
            $receivedMappingsIterable = $this->mappingService->getListByExternalIds(
                $datasetEntityType,
                $targetPortalNodeKey,
                $externalIds
            );

            foreach ($receivedMappingsIterable as $externalId => $receivedMapping) {
                $original = $originalReflectionMappings[$externalId];

                if ($receivedMapping->getMappingNodeKey()->equals($original->getMappingNodeKey())) {
                    continue;
                }

                try {
                    $this->mappingService->merge(
                        $receivedMapping->getMappingNodeKey(),
                        $original->getMappingNodeKey()
                    );
                } catch (\Throwable $exception) {
                    $exceptions[] = $exception;
                }
            }
        }

        if ($exceptions) {
            throw new CumulativeMappingException('Errors occured while merging mapping nodes.', ...$exceptions);
        }
    }
}
