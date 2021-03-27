<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\EmitContextFactory;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Psr\Log\LoggerInterface;

class ExplorationActor implements ExplorationActorInterface
{
    public const CHUNK_SIZE = 50;

    private LoggerInterface $logger;

    private MappingServiceInterface $mappingService;

    private EmissionActorInterface $emissionActor;

    private EmitContextFactory $emitContextFactory;

    private PublisherInterface $publisher;

    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    public function __construct(
        LoggerInterface $logger,
        MappingServiceInterface $mappingService,
        EmissionActorInterface $emissionActor,
        EmitContextFactory $emitContextFactory,
        PublisherInterface $publisher,
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory
    ) {
        $this->logger = $logger;
        $this->mappingService = $mappingService;
        $this->emissionActor = $emissionActor;
        $this->emitContextFactory = $emitContextFactory;
        $this->publisher = $publisher;
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
    }

    public function performExploration(
        string $entityClassName,
        ExplorerStackInterface $stack,
        ExploreContextInterface $context
    ): void {
        $mappings = [];
        $directEmitter = new DirectEmitter($entityClassName);
        $emissionStack = $this->emitterStackBuilderFactory
            ->createEmitterStackBuilder($context->getPortalNodeKey(), $entityClassName)
            ->push($directEmitter)
            ->pushDecorators()
            ->build();

        $emitContext = $this->emitContextFactory->createContext($context->getPortalNodeKey());

        try {
            /** @var DatasetEntityContract|string|int|null $entity */
            foreach ($stack->next($context) as $entity) {
                if ($entity instanceof DatasetEntityContract && ($primaryKey = $entity->getPrimaryKey()) !== null) {
                    $mapping = $this->mappingService->get($entityClassName, $context->getPortalNodeKey(), $primaryKey);

                    $directEmitter->getMappedEntities()->clear();
                    $directEmitter->getMappedEntities()->push([new MappedDatasetEntityStruct($mapping, $entity)]);
                    $this->emissionActor->performEmission(
                        new TypedMappingCollection($entityClassName, [$mapping]),
                        clone $emissionStack,
                        $emitContext,
                    );
                } elseif (\is_string($entity) || \is_int($entity)) {
                    // TODO: use batch operations by using $this->mappingService->getListByExternalIds()
                    $mappings[] = $this->mappingService->get($entityClassName, $context->getPortalNodeKey(), (string) $entity);

                    if (\count($mappings) >= self::CHUNK_SIZE) {
                        $this->publisher->publishBatch(new MappingCollection($mappings));
                        $mappings = [];
                    }
                }
                // TODO: log this
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EXPLORE_NO_THROW(), [
                'type' => $entityClassName,
                'stack' => $stack,
                'exception' => $exception,
            ]);
        } finally {
            if ($mappings !== []) {
                $this->publisher->publishBatch(new MappingCollection($mappings));
            }
        }
    }
}
