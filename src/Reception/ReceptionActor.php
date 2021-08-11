<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Core\Reception\Contract\ReceptionActorInterface;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Core\Router\CumulativeMappingException;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiveContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister;
use Psr\Log\LoggerInterface;

class ReceptionActor implements ReceptionActorInterface
{
    private LoggerInterface $logger;

    private DeepObjectIteratorContract $deepObjectIterator;

    private MappingPersister $mappingPersister;

    public function __construct(
        LoggerInterface $logger,
        DeepObjectIteratorContract $deepObjectIterator,
        MappingPersister $mappingPersister
    ) {
        $this->logger = $logger;
        $this->deepObjectIterator = $deepObjectIterator;
        $this->mappingPersister = $mappingPersister;
    }

    public function performReception(
        TypedDatasetEntityCollection $entities,
        ReceiverStackInterface $stack,
        ReceiveContextInterface $context
    ): void {
        if ($entities->count() < 1) {
            return;
        }

        foreach ($this->deepObjectIterator->iterate($entities) as $object) {
            if (!$object instanceof DatasetEntityContract) {
                continue;
            }

            $attachable = new PrimaryKeyChangesAttachable(\get_class($object));
            $attachable->setForeignKey($object->getPrimaryKey());
            $object->attach($attachable);
        }

        try {
            \iterable_to_array($stack->next($entities, $context));
            $this->saveMappings($context->getPortalNodeKey(), $entities);
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::RECEIVE_NO_THROW(), [
                'type' => $entities->getType(),
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

    private function saveMappings(PortalNodeKeyInterface $targetPortalNodeKey, DatasetEntityCollection $entities): void
    {
        $payload = new MappingPersistPayload($targetPortalNodeKey);

        foreach ($this->deepObjectIterator->iterate($entities) as $entity) {
            if (!$entity instanceof DatasetEntityContract) {
                // no entity
                continue;
            }

            $primaryKeyChanges = $entity->getAttachment(PrimaryKeyChangesAttachable::class);

            if (!$primaryKeyChanges instanceof PrimaryKeyChangesAttachable
                || $primaryKeyChanges->getFirstForeignKey() === $primaryKeyChanges->getForeignKey()) {
                // no change
                continue;
            }

            $mapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

            if (!$mapping instanceof PrimaryKeySharingMappingStruct) {
                // no mapping
                continue;
            }

            if ($mapping->getExternalId() === null) {
                // unmappable
                continue;
            }

            if ($primaryKeyChanges->getFirstForeignKey() === null) {
                $payload->create($mapping->getMappingNodeKey(), $primaryKeyChanges->getForeignKey());
            } elseif ($primaryKeyChanges->getForeignKey() === null) {
                $payload->delete($mapping->getMappingNodeKey());
            } else {
                $payload->update($mapping->getMappingNodeKey(), $primaryKeyChanges->getForeignKey());
            }
        }

        $this->mappingPersister->persist($payload);
    }
}
