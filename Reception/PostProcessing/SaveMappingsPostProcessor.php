<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Reception\Contract\PostProcessorContract;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistDeletePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistUpdatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityPersistActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Log\LoggerInterface;

class SaveMappingsPostProcessor extends PostProcessorContract
{
    private DeepObjectIteratorContract $deepObjectIterator;

    private IdentityPersistActionInterface $mappingPersistAction;

    private LoggerInterface $logger;

    public function __construct(
        DeepObjectIteratorContract $deepObjectIterator,
        IdentityPersistActionInterface $mappingPersistAction,
        LoggerInterface $logger
    ) {
        $this->deepObjectIterator = $deepObjectIterator;
        $this->mappingPersistAction = $mappingPersistAction;
        $this->logger = $logger;
    }

    public function handle(PostReceptionEvent $event): void
    {
        $saveMappingsData = \iterable_to_array($event->getContext()->getPostProcessingBag()->of(SaveMappingsData::class));
        $entities = \array_map(static fn (SaveMappingsData $data): DatasetEntityContract => $data->getEntity(), $saveMappingsData);

        $this->saveMappings($event->getContext()->getPortalNodeKey(), \iterable_to_array($entities));

        foreach ($saveMappingsData as $saveMappingData) {
            $event->getContext()->getPostProcessingBag()->remove($saveMappingData);
        }
    }

    /**
     * @param DatasetEntityContract[] $receivedEntityData
     */
    private function saveMappings(PortalNodeKeyInterface $targetPortalNodeKey, array $receivedEntityData): void
    {
        if ($receivedEntityData === []) {
            return;
        }

        $payload = new IdentityPersistPayload($targetPortalNodeKey, new IdentityPersistPayloadCollection());

        foreach ($this->deepObjectIterator->iterate($receivedEntityData) as $entity) {
            if (!$entity instanceof DatasetEntityContract) {
                // no entity
                continue;
            }

            $primaryKeyChanges = $entity->getAttachment(PrimaryKeyChangesAttachable::class);

            if (!$primaryKeyChanges instanceof PrimaryKeyChangesAttachable) {
                // no change
                continue;
            }

            $externalId = $primaryKeyChanges->getForeignKey();
            $firstForeignKey = $primaryKeyChanges->getFirstForeignKey();

            if ($firstForeignKey === $externalId) {
                // no change
                continue;
            }

            $mapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

            if (!$mapping instanceof PrimaryKeySharingMappingStruct) {
                $this->logger->critical('Unknown mapping origin', [
                    'code' => 1637527920,
                    'firstForeignKey' => $firstForeignKey,
                    'externalId' => $externalId,
                    'entityType' => \get_class($entity),
                ]);

                continue;
            }

            if ($mapping->getExternalId() === null) {
                $this->logger->critical('Invalid mapping origin', [
                    'code' => 1637527921,
                    'firstForeignKey' => $firstForeignKey,
                    'externalId' => $externalId,
                    'entityType' => \get_class($entity),
                ]);

                continue;
            }

            if ($firstForeignKey === null && $externalId !== null) {
                $payload->getMappingPersistPayloads()->push([
                    new IdentityPersistCreatePayload($mapping->getMappingNodeKey(), $externalId),
                ]);
            } elseif ($externalId === null) {
                $payload->getMappingPersistPayloads()->push([
                    new IdentityPersistDeletePayload($mapping->getMappingNodeKey()),
                ]);
            } else {
                $payload->getMappingPersistPayloads()->push([
                    new IdentityPersistUpdatePayload($mapping->getMappingNodeKey(), $externalId),
                ]);
            }
        }

        $this->mappingPersistAction->persist($payload);
    }
}
