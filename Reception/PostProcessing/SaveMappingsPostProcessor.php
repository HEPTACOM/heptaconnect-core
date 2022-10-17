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
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayloadContract;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistUpdatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityPersistActionInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Psr\Log\LoggerInterface;

final class SaveMappingsPostProcessor extends PostProcessorContract
{
    public function __construct(private DeepObjectIteratorContract $deepObjectIterator, private IdentityPersistActionInterface $identityPersistAction, private LoggerInterface $logger)
    {
    }

    public function handle(PostReceptionEvent $event): void
    {
        $saveMappingsData = \iterable_to_array($event->getContext()->getPostProcessingBag()->of(SaveMappingsData::class));
        $entities = \array_map(static fn (SaveMappingsData $data): DatasetEntityContract => $data->getEntity(), $saveMappingsData);

        $this->saveMappings($event->getContext()->getPortalNodeKey(), $entities);

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

            $persistPayload = $this->getPersistPayloadFromEntity($entity);

            if ($persistPayload === null) {
                continue;
            }

            $payload->getIdentityPersistPayloads()->push([$persistPayload]);
        }

        $this->identityPersistAction->persist($payload);
    }

    private function getPersistPayloadFromEntity(DatasetEntityContract $entity): ?IdentityPersistPayloadContract
    {
        $primaryKeyChanges = $entity->getAttachment(PrimaryKeyChangesAttachable::class);

        if (!$primaryKeyChanges instanceof PrimaryKeyChangesAttachable) {
            // no change
            return null;
        }

        $externalId = $primaryKeyChanges->getForeignKey();
        $firstForeignKey = $primaryKeyChanges->getFirstForeignKey();

        if ($firstForeignKey === $externalId) {
            // no change
            return null;
        }

        $mapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

        if (!$mapping instanceof PrimaryKeySharingMappingStruct) {
            $this->logger->critical('Unknown mapping origin', [
                'code' => 1637527920,
                'firstForeignKey' => $firstForeignKey,
                'externalId' => $externalId,
                'entityType' => $entity::class,
            ]);

            return null;
        }

        if ($mapping->getExternalId() === null) {
            $this->logger->critical('Invalid mapping origin', [
                'code' => 1637527921,
                'firstForeignKey' => $firstForeignKey,
                'externalId' => $externalId,
                'entityType' => $entity::class,
            ]);

            return null;
        }

        if ($firstForeignKey === null && $externalId !== null) {
            return new IdentityPersistCreatePayload($mapping->getMappingNodeKey(), $externalId);
        } elseif ($externalId === null) {
            return new IdentityPersistDeletePayload($mapping->getMappingNodeKey());
        }

        return new IdentityPersistUpdatePayload($mapping->getMappingNodeKey(), $externalId);
    }
}
