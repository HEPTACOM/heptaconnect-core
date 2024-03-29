<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\DirectEmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationFlowExplorersFactoryInterface;
use Heptacom\HeptaConnect\Core\Job\Contract\JobDispatcherContract;
use Heptacom\HeptaConnect\Core\Job\Transition\Contract\ExploredPrimaryKeysToJobsConverterInterface;
use Heptacom\HeptaConnect\Core\Storage\PrimaryKeyToEntityHydrator;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
final class ExplorationFlowExplorersFactory implements ExplorationFlowExplorersFactoryInterface
{
    public function __construct(
        private DirectEmissionFlowEmittersFactoryInterface $directEmissionFlowEmittersFactory,
        private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        private EmitterStackProcessorInterface $emitterStackProcessor,
        private EmitContextFactoryInterface $emitContextFactory,
        private ExploredPrimaryKeysToJobsConverterInterface $exploredPksToJobsConverter,
        private JobDispatcherContract $jobDispatcher,
        private PrimaryKeyToEntityHydrator $primaryKeyToEntityHydrator,
        private IdentityMapActionInterface $identityMapAction,
        private LoggerInterface $logger,
        private int $jobBatchSize,
        private int $identityBatchSize,
        private int $emissionBatchSize
    ) {
    }

    public function createExplorers(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): ExplorerCollection
    {
        $directEmitter = new DirectEmitter($entityType);
        $emissionStackBuilder = $this->emitterStackBuilderFactory
            ->createEmitterStackBuilder($portalNodeKey, $entityType)
            ->push($directEmitter)
            ->pushDecorators();

        foreach ($this->directEmissionFlowEmittersFactory->createEmitters($portalNodeKey, $entityType) as $emitter) {
            $emissionStackBuilder = $emissionStackBuilder->push($emitter);
        }

        return new ExplorerCollection([
            new EmissionJobDispatchingExplorer(
                $entityType,
                $this->exploredPksToJobsConverter,
                $this->jobDispatcher,
                $this->logger,
                $this->jobBatchSize
            ),
            new IdentityMappingExplorer(
                $entityType,
                $this->primaryKeyToEntityHydrator,
                $this->identityMapAction,
                $this->identityBatchSize
            ),
            new DirectEmittingExplorer(
                $entityType,
                $directEmitter,
                $this->emitterStackProcessor,
                $emissionStackBuilder->build(),
                $this->emitContextFactory->createContext($portalNodeKey, true),
                $this->logger,
                $this->emissionBatchSize
            ),
        ]);
    }
}
