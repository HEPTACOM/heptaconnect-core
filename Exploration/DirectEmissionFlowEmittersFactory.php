<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\IdentityMappingEmitter;
use Heptacom\HeptaConnect\Core\Exploration\Contract\DirectEmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;

final class DirectEmissionFlowEmittersFactory implements DirectEmissionFlowEmittersFactoryInterface
{
    private EmissionFlowEmittersFactoryInterface $emissionFlowEmittersFactory;

    private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator;

    private IdentityMapActionInterface $identityMapAction;

    private int $identityBatchSize;

    public function __construct(
        EmissionFlowEmittersFactoryInterface $emissionFlowEmittersFactory,
        PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        IdentityMapActionInterface $identityMapAction,
        int $identityBatchSize
    ) {
        $this->emissionFlowEmittersFactory = $emissionFlowEmittersFactory;
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

        $result->push($this->emissionFlowEmittersFactory->createEmitters($portalNodeKey, $entityType));

        return $result;
    }
}
