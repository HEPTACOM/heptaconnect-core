<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;

interface MappingServiceInterface
{
    public function getSendingPortalNodeId(MappingInterface $mapping): ?string;

    public function addException(MappingInterface $mapping, \Throwable $exception): void;

    public function setExternalId(MappingInterface $mapping, string $externalId): void;
}
