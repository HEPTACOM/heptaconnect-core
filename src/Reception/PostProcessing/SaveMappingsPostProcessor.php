<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\MappingPersister\Contract\MappingPersisterContract;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;

class SaveMappingsPostProcessor extends PostProcessorContract
{
    private DeepObjectIteratorContract $deepObjectIterator;

    private MappingPersisterContract $mappingPersister;

    public function __construct(
        DeepObjectIteratorContract $deepObjectIterator,
        MappingPersisterContract $mappingPersister
    ) {
        $this->deepObjectIterator = $deepObjectIterator;
        $this->mappingPersister = $mappingPersister;
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

        $payload = new MappingPersistPayload($targetPortalNodeKey);

        foreach ($this->deepObjectIterator->iterate($receivedEntityData) as $entity) {
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
