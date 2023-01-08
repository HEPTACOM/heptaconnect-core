<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\Contract\PrimaryKeyAwareInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;

final class IdentityMappingEmitter extends AbstractBufferedResultProcessingEmitter
{
    public function __construct(
        EntityType $entityType,
        private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        private IdentityMapActionInterface $identityMapAction,
        int $batchSize
    ) {
        parent::__construct($entityType, $batchSize);
    }

    protected function processBuffer(TypedDatasetEntityCollection $buffer, EmitContextInterface $context): void
    {
        $primaryKeys = new StringCollection();
        $primaryKeys->pushIgnoreInvalidItems($buffer->map(
            static fn (PrimaryKeyAwareInterface $entity): ?string => $entity->getPrimaryKey()
        ));

        $this->identityMapAction->map(new IdentityMapPayload(
            $context->getPortalNodeKey(),
            $this->primaryKeyToEntityHydrator->hydrate($this->getSupportedEntityType(), $primaryKeys)
        ));
    }
}
