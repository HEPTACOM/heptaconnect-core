<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception\PostProcessing;

use Heptacom\HeptaConnect\Core\Event\PostReceptionEvent;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Reception\Support\PrimaryKeyChangesAttachable;
use Heptacom\HeptaConnect\Core\Router\CumulativeMappingException;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;

class SaveMappingsPostProcessor extends PostProcessorContract
{
    private MappingServiceInterface $mappingService;

    private DeepObjectIteratorContract $deepObjectIterator;

    public function __construct(
        MappingServiceInterface $mappingService,
        DeepObjectIteratorContract $deepObjectIterator
    ) {
        $this->mappingService = $mappingService;
        $this->deepObjectIterator = $deepObjectIterator;
    }

    public function handle(PostReceptionEvent $event): void
    {
        $entities = iterable_map(
            $event->getContext()->getPostProcessingBag()->of(SaveMappingsData::class),
            static fn (SaveMappingsData $data) => $data->getEntity()
        );

        $this->saveMappings($event->getContext()->getPortalNodeKey(), iterable_to_array($entities));

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
        /** @var MappingInterface[] $originalReflectionMappings */
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
