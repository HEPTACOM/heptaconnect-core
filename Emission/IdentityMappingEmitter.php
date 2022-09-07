<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Dataset\Base\TypedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;

final class IdentityMappingEmitter extends AbstractBufferedResultProcessingEmitter
{
    private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator;

    private IdentityMapActionInterface $identityMapAction;

    public function __construct(
        EntityType $entityType,
        PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        IdentityMapActionInterface $identityMapAction,
        int $batchSize
    ) {
        parent::__construct($entityType, $batchSize);

        $this->identityMapAction = $identityMapAction;
        $this->primaryKeyToEntityHydrator = $primaryKeyToEntityHydrator;
    }

    protected function processBuffer(TypedDatasetEntityCollection $buffer, EmitContextInterface $context): void
    {
        $this->identityMapAction->map(new IdentityMapPayload(
            $context->getPortalNodeKey(),
            $this->primaryKeyToEntityHydrator->hydrate(
                $this->getSupportedEntityType(),
                new StringCollection($buffer->column('getPrimaryKey'))
            )
        ));
    }
}
