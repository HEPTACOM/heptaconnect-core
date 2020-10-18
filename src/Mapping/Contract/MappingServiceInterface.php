<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Core\Mapping\Exception\MappingNodeAreUnmergableException;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
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

    public function save(MappingInterface $mapping): void;

    public function reflect(MappingInterface $mapping, PortalNodeKeyInterface $portalNodeKey): MappingInterface;

    /**
     * @throws MappingNodeAreUnmergableException
     */
    public function merge(MappingNodeKeyInterface $mergeFrom, MappingNodeKeyInterface $mergeInto): void;
}
