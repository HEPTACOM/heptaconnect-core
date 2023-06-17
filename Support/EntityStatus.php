<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Support;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\EntityStatusContract;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;

final class EntityStatus extends EntityStatusContract
{
    public function isMappedByEmitter(DatasetEntityContract $entity): bool
    {
        $sharer = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

        if (!($sharer instanceof PrimaryKeySharingMappingStruct)) {
            return false;
        }

        return \is_string($sharer->getExternalId());
    }
}
