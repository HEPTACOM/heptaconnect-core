<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;

/**
 * @extends AbstractBufferedResultProcessingExplorer<string>
 */
final class IdentityMappingExplorer extends AbstractBufferedResultProcessingExplorer
{
    public function __construct(
        EntityType $entityType,
        private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        private IdentityMapActionInterface $identityMapAction,
        int $batchSize
    ) {
        parent::__construct($entityType, $batchSize);
    }

    protected function createBuffer(): CollectionInterface
    {
        return new StringCollection();
    }

    protected function processBuffer(CollectionInterface $buffer, ExploreContextInterface $context): void
    {
        $this->identityMapAction->map(new IdentityMapPayload(
            $context->getPortalNodeKey(),
            $this->primaryKeyToEntityHydrator->hydrate($this->getSupportedEntityType(), new StringCollection($buffer))
        ));
    }

    protected function pushBuffer($value, CollectionInterface $buffer, ExploreContextInterface $context): void
    {
        if (\is_string($value) || \is_int($value)) {
            $buffer->push([(string) $value]);
        }
    }
}
