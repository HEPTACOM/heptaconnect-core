<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface MappingServiceInterface
{
    public function addException(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        \Throwable $exception
    ): void;

    public function get(
        string $entityType,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): MappingInterface;

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityType
     *
     * @return MappingInterface[]
     *
     * @psalm-return iterable<string, \Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface>
     */
    public function getListByExternalIds(
        string $entityType,
        PortalNodeKeyInterface $portalNodeKey,
        array $externalIds
    ): iterable;

    public function ensurePersistence(MappingComponentCollection $mappingComponentCollection): void;

    public function save(MappingInterface $mapping): void;

    public function reflect(MappingInterface $mapping, PortalNodeKeyInterface $portalNodeKey): MappingInterface;

    /**
     * @throws MappingNodeAreUnmergableException
     */
    public function merge(MappingNodeKeyInterface $mergeFrom, MappingNodeKeyInterface $mergeInto): void;
}
