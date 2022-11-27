<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage;

use Heptacom\HeptaConnect\Dataset\Base\AttachmentCollection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DependencyCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;

/**
 * @deprecated this is useful until the rewrite of identity handling, which is subject for the next major version
 */
class PrimaryKeyToEntityHydrator
{
    /**
     * @var array<class-string, \ReflectionClass<DatasetEntityContract>>
     */
    private array $factoryCache = [];

    public function hydrate(EntityType $entityType, StringCollection $primaryKeys): TypedDatasetEntityCollection
    {
        $factory = $this->factoryCache[(string) $entityType] ??= new \ReflectionClass((string) $entityType);

        return new TypedDatasetEntityCollection(
            $entityType,
            $primaryKeys->map(static function (string $pk) use ($factory): DatasetEntityContract {
                $entity = $factory->newInstanceWithoutConstructor();

                \Closure::bind(function (DatasetEntityContract $entity): void {
                    $entity->attachments = new AttachmentCollection();
                    $entity->dependencies = new DependencyCollection();
                }, null, $entity)($entity);

                $entity->setPrimaryKey($pk);

                return $entity;
            })
        );
    }
}
