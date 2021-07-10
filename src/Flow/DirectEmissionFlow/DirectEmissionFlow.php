<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\EmitContextFactory;
use Heptacom\HeptaConnect\Core\Exploration\DirectEmitter;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionFlowContract;
use Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\DirectEmissionResult;
use Heptacom\HeptaConnect\Portal\Base\Flow\DirectEmission\Exception\UnidentifiedEntityException;
use Heptacom\HeptaConnect\Portal\Base\Profiling\NullProfiler;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerAwareInterface;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DirectEmissionFlow extends DirectEmissionFlowContract implements LoggerAwareInterface, ProfilerAwareInterface
{
    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    private EmitContextFactory $emitContextFactory;

    private MappingServiceInterface $mappingService;

    private EmissionActorInterface $emissionActor;

    private LoggerInterface $logger;

    private ProfilerContract $profiler;

    public function __construct(
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        EmitContextFactory $emitContextFactory,
        MappingServiceInterface $mappingService,
        EmissionActorInterface $emissionActor
    ) {
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
        $this->emitContextFactory = $emitContextFactory;
        $this->mappingService = $mappingService;
        $this->emissionActor = $emissionActor;
        $this->logger = new NullLogger();
        $this->profiler = new NullProfiler();
    }

    public function run(PortalNodeKeyInterface $portalNodeKey, DatasetEntityCollection $entities): DirectEmissionResult
    {
        $result = new DirectEmissionResult();
        $emitContext = $this->emitContextFactory->createContext($portalNodeKey, true);

        /** @var DatasetEntityContract[] $unidentifiedEntities */
        $unidentifiedEntities = \iterable_to_array($entities->filter(
            static fn (DatasetEntityContract $entity): bool => \is_null($entity->getPrimaryKey())
        ));

        foreach ($unidentifiedEntities as $unidentifiedEntity) {
            $exception = new UnidentifiedEntityException($unidentifiedEntity);

            $result->addError($exception);
            $this->logger->error($exception->getMessage());
        }

        foreach ($entities->groupByType() as $type => $entitiesByType) {
            /** @var string[] $externalIds */
            $externalIds = \array_filter(\iterable_to_array($entitiesByType->map(
                static fn (DatasetEntityContract $entity): ?string => $entity->getPrimaryKey()
            )));

            try {
                $directEmitter = new DirectEmitter($type);
                $directEmitter->getEntities()->push($entitiesByType);

                $emissionStack = $this->emitterStackBuilderFactory
                    ->createEmitterStackBuilder($portalNodeKey, $type)
                    ->push($directEmitter)
                    ->pushDecorators()
                    ->build();

                \iterable_to_array($this->mappingService->getListByExternalIds($type, $portalNodeKey, $externalIds));
                $this->profiler->start('EmissionActor::performEmission', 'DirectEmissionFlow');
                $this->emissionActor->performEmission($externalIds, $emissionStack, $emitContext);
                $this->profiler->stop();
            } catch (\Throwable $exception) {
                $this->profiler->stop($exception);
                $result->addError($exception);
                $this->logger->error($exception->getMessage());
            }
        }

        return $result;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setProfiler(ProfilerContract $profiler): void
    {
        $this->profiler = $profiler;
    }
}
