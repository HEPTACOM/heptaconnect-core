<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class MappingService implements MappingServiceInterface
{
    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function addException(MappingInterface $mapping, \Throwable $exception): void
    {
        // TODO: Implement addException() method.
    }

    public function save(MappingInterface $mapping): void
    {
        // TODO: Implement save() method.
    }

    public function reflect(MappingInterface $mapping, string $portalNodeId): MappingInterface
    {
        // TODO: Implement reflect() method.

        return $mapping;
    }
}
