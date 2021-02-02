<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

interface MappingServiceInterface
{
    public function addException(MappingInterface $mapping, \Throwable $exception): void;

    public function get(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): MappingInterface;

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $datasetEntityClassName
     *
     * @return MappingInterface[]
     *
     * @psalm-return iterable<string, \Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface>
     */
    public function getListByExternalIds(
        string $datasetEntityClassName,
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
