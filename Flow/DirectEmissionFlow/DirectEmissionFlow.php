<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Flow\DirectEmissionFlow;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackProcessorInterface;
use Heptacom\HeptaConnect\Core\Emission\EmitContextFactory;
use Heptacom\HeptaConnect\Core\Exploration\Contract\DirectEmissionEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\DirectEmitter;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
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

final class DirectEmissionFlow extends DirectEmissionFlowContract implements LoggerAwareInterface, ProfilerAwareInterface
{
    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    private EmitContextFactory $emitContextFactory;

    private EmitterStackProcessorInterface $stackProcessor;

    private DirectEmissionEmittersFactoryInterface $directEmissionEmittersFactory;

    private LoggerInterface $logger;

    private ProfilerContract $profiler;

    public function __construct(
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        EmitContextFactory $emitContextFactory,
        EmitterStackProcessorInterface $stackProcessor,
        DirectEmissionEmittersFactoryInterface $directEmissionEmittersFactory
    ) {
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
        $this->emitContextFactory = $emitContextFactory;
        $this->stackProcessor = $stackProcessor;
        $this->directEmissionEmittersFactory = $directEmissionEmittersFactory;
        $this->logger = new NullLogger();
        $this->profiler = new NullProfiler();
    }

    public function run(PortalNodeKeyInterface $portalNodeKey, DatasetEntityCollection $entities): DirectEmissionResult
    {
        $result = new DirectEmissionResult();
        $emitContext = $this->emitContextFactory->createContext($portalNodeKey, true);

        /** @var DatasetEntityContract[] $unidentifiedEntities */
        $unidentifiedEntities = \iterable_to_array($entities->filter(
            static fn (DatasetEntityContract $entity): bool => $entity->getPrimaryKey() === null
        ));

        foreach ($unidentifiedEntities as $unidentifiedEntity) {
            $exception = new UnidentifiedEntityException($unidentifiedEntity);

            $result->addError($exception);
            $this->logger->error($exception->getMessage());
        }

        /** @var class-string<DatasetEntityContract> $type */
        foreach ($entities->groupByType() as $type => $entitiesByType) {
            $entityType = new EntityType($type);
            /** @var string[] $externalIds */
            $externalIds = \array_filter(\iterable_to_array($entitiesByType->map(
                static fn (DatasetEntityContract $entity): ?string => $entity->getPrimaryKey()
            )), static fn (?string $primaryKey): bool => $primaryKey !== null);

            try {
                $directEmitter = new DirectEmitter($entityType);
                $directEmitter->getEntities()->push($entitiesByType);

                $emissionStackBuilder = $this->emitterStackBuilderFactory
                    ->createEmitterStackBuilder($portalNodeKey, $entityType)
                    ->push($directEmitter)
                    ->pushDecorators();

                foreach ($this->directEmissionEmittersFactory->createEmitters($portalNodeKey, $entityType) as $emitter) {
                    $emissionStackBuilder = $emissionStackBuilder->push($emitter);
                }

                $this->profiler->start('EmissionActor::performEmission', 'DirectEmissionFlow');
                $this->stackProcessor->processStack($externalIds, $emissionStackBuilder->build(), $emitContext);
                $this->profiler->stop();
            } catch (\Throwable $exception) {
                $this->profiler->stop($exception);
                $result->addError($exception);
                $this->logger->error($exception->getMessage());
            }
        }

        return $result;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setProfiler(ProfilerContract $profiler): void
    {
        $this->profiler = $profiler;
    }
}
