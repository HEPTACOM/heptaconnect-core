<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\IdentityMappingEmitter;
use Heptacom\HeptaConnect\Core\Exploration\Contract\DirectEmissionEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;

final class DirectEmissionEmittersFactory implements DirectEmissionEmittersFactoryInterface
{
    private EmissionEmittersFactoryInterface $emissionEmittersFactory;

    private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator;

    private IdentityMapActionInterface $identityMapAction;

    private int $identityBatchSize;

    public function __construct(
        EmissionEmittersFactoryInterface $emissionEmittersFactory,
        PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        IdentityMapActionInterface $identityMapAction,
        int $identityBatchSize
    ) {
        $this->emissionEmittersFactory = $emissionEmittersFactory;
        $this->primaryKeyToEntityHydrator = $primaryKeyToEntityHydrator;
        $this->identityMapAction = $identityMapAction;
        $this->identityBatchSize = $identityBatchSize;
    }

    public function createEmitters(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): EmitterCollection
    {
        $result = new EmitterCollection([
            new IdentityMappingEmitter(
                $entityType,
                $this->primaryKeyToEntityHydrator,
                $this->identityMapAction,
                $this->identityBatchSize
            ),
        ]);

        $result->push($this->emissionEmittersFactory->createEmitters($portalNodeKey, $entityType));

        return $result;
    }
}
