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
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
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

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        LoggerInterface $logger,
        MappingServiceInterface $mappingService,
        EmissionActorInterface $emissionActor,
        EmitContextFactory $emitContextFactory,
        PublisherInterface $publisher,
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->logger = $logger;
        $this->mappingService = $mappingService;
        $this->emissionActor = $emissionActor;
        $this->emitContextFactory = $emitContextFactory;
        $this->publisher = $publisher;
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function performExploration(
        string $entityClassName,
        ExplorerStackInterface $stack,
        ExploreContextInterface $context
    ): void {
        $directEmitter = new DirectEmitter($entityClassName);
        $emissionStack = $this->emitterStackBuilderFactory
            ->createEmitterStackBuilder($context->getPortalNodeKey(), $entityClassName)
            ->push($directEmitter)
            ->pushDecorators()
            ->build();

        $emitContext = $this->emitContextFactory->createContext($context->getPortalNodeKey());

        $mappings = [];
        $primaryKeys = [];
        $directEmitter->getEntities()->clear();

        try {
            /** @var DatasetEntityContract|string|int|null $entity */
            foreach ($stack->next($context) as $entity) {
                if ($entity instanceof DatasetEntityContract && ($primaryKey = $entity->getPrimaryKey()) !== null) {
                    // TODO: use batch operations by using $this->mappingService->getListByExternalIds()
                    $this->mappingService->get($entityClassName, $context->getPortalNodeKey(), $primaryKey);

                    $primaryKeys[] = $primaryKey;
                    $directEmitter->getEntities()->push([$entity]);

                    $this->logger->debug(\sprintf(
                        'ExplorationActor: Entity was explored and direct emission is prepared. PortalNode: %s; Type: %s; PrimaryKey: %s',
                        $this->storageKeyGenerator->serialize($context->getPortalNodeKey()),
                        $entityClassName,
                        $primaryKey
                    ));

                    if (\count($primaryKeys) >= self::CHUNK_SIZE) {
                        $this->flushDirectEmissions(
                            $emissionStack,
                            $emitContext,
                            $context->getPortalNodeKey(),
                            $entityClassName,
                            $primaryKeys
                        );

                        $primaryKeys = [];
                        $directEmitter->getEntities()->clear();
                    }
                } elseif (\is_string($entity) || \is_int($entity)) {
                    // TODO: use batch operations by using $this->mappingService->getListByExternalIds()
                    $mappings[] = $this->mappingService->get($entityClassName, $context->getPortalNodeKey(), (string) $entity);

                    $this->logger->debug(\sprintf(
                        'ExplorationActor: Entity was explored and publication is prepared. PortalNode: %s; Type: %s; PrimaryKey: %s',
                        $this->storageKeyGenerator->serialize($context->getPortalNodeKey()),
                        $entityClassName,
                        $primaryKey
                    ));

                    if (\count($mappings) >= self::CHUNK_SIZE) {
                        $this->flushPublications(
                            $context->getPortalNodeKey(),
                            $entityClassName,
                            $mappings
                        );

                        $mappings = [];
                    }
                } else {
                    if ($entity instanceof DatasetEntityContract && $entity->getPrimaryKey() === null) {
                        $this->logger->warning(\sprintf(
                            'ExplorationActor: Entity with empty primary key was explored. Type: %s; PortalNode: %s',
                            $entityClassName,
                            $this->storageKeyGenerator->serialize($context->getPortalNodeKey())
                        ));
                    } else {
                        $this->logger->warning(\sprintf(
                            'ExplorationActor: Empty or invalid primary key was explored. Type: %s; PortalNode: %s',
                            $entityClassName,
                            $this->storageKeyGenerator->serialize($context->getPortalNodeKey())
                        ));
                    }
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EXPLORE_NO_THROW(), [
                'type' => $entityClassName,
                'stack' => $stack,
                'exception' => $exception,
            ]);
        } finally {
            if ($primaryKeys !== []) {
                $this->flushDirectEmissions(
                    $emissionStack,
                    $emitContext,
                    $context->getPortalNodeKey(),
                    $entityClassName,
                    $primaryKeys
                );
            }

            if ($mappings !== []) {
                $this->flushPublications(
                    $context->getPortalNodeKey(),
                    $entityClassName,
                    $mappings
                );
            }
        }
    }

    protected function flushDirectEmissions(
        EmitterStackInterface $emissionStack,
        EmitContextInterface $emitContext,
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName,
        array $primaryKeys
    ): void {
        $this->logger->debug(\sprintf(
            'ExplorationActor: Flush a batch of direct emissions. PortalNode: %s; Type: %s, PrimaryKeys: %s',
            $this->storageKeyGenerator->serialize($portalNodeKey),
            $entityClassName,
            \implode(',', $primaryKeys)
        ));

        $this->emissionActor->performEmission($primaryKeys, clone $emissionStack, $emitContext);
    }

    protected function flushPublications(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName,
        array $mappings
    ): void {
        $this->logger->debug(\sprintf(
            'ExplorationActor: Flush a batch of publications. PortalNode: %s; Type: %s, PrimaryKeys: %s',
            $this->storageKeyGenerator->serialize($portalNodeKey),
            $entityClassName,
            \implode(',', \array_map(fn (MappingInterface $mapping) => $mapping->getExternalId(), $mappings))
        ));

        $this->publisher->publishBatch(new MappingCollection($mappings));
    }
}
