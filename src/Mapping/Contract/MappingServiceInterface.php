<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Mapping\Contract;

use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;

interface MappingServiceInterface
{
    public function getSendingPortalNodeId(MappingInterface $mapping): ?string;

    /**
     * @return class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
     */
    public function getDatasetEntityClassName(MappingInterface $mapping): string;
}
