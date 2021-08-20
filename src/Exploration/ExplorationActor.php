<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorationActorInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExploreContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Publication\Contract\PublisherInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class ExplorationActor implements ExplorationActorInterface
{
    public const CHUNK_SIZE_EMISSION = 10;

    public const CHUNK_SIZE_PUBLICATION = 50;

    private LoggerInterface $logger;

    private MappingServiceInterface $mappingService;

    private EmissionActorInterface $emissionActor;

    private EmitContextFactoryInterface $emitContextFactory;

    private PublisherInterface $publisher;

    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        LoggerInterface $logger,
        MappingServiceInterface $mappingService,
        EmissionActorInterface $emissionActor,
        EmitContextFactoryInterface $emitContextFactory,
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

        $emitContext = $this->emitContextFactory->createContext($context->getPortalNodeKey(), true);

        $publicationPks = [];
        $emissionPks = [];
        $directEmitter->getEntities()->clear();

        try {
            /** @var DatasetEntityContract|string|int|null $entity */
            foreach ($stack->next($context) as $entity) {
                if ($entity instanceof DatasetEntityContract && ($primaryKey = $entity->getPrimaryKey()) !== null) {
                    $emissionPks[] = $primaryKey;
                    $directEmitter->getEntities()->push([$entity]);

                    $this->logger->debug(\sprintf(
                        'ExplorationActor: Entity was explored and direct emission is prepared. PortalNode: %s; Type: %s; PrimaryKey: %s',
                        $this->storageKeyGenerator->serialize($context->getPortalNodeKey()),
                        $entityClassName,
                        $primaryKey
                    ));

                    if (\count($emissionPks) >= self::CHUNK_SIZE_EMISSION) {
                        $this->flushDirectEmissions(
                            $emissionStack,
                            $emitContext,
                            $context->getPortalNodeKey(),
                            $entityClassName,
                            $emissionPks
                        );

                        $emissionPks = [];
                        $directEmitter->getEntities()->clear();
                    }
                } elseif (\is_string($entity) || \is_int($entity)) {
                    $publicationPks[] = (string) $entity;

                    $this->logger->debug(\sprintf(
                        'ExplorationActor: Entity was explored and publication is prepared. PortalNode: %s; Type: %s; PrimaryKey: %s',
                        $this->storageKeyGenerator->serialize($context->getPortalNodeKey()),
                        $entityClassName,
                        (string) $entity
                    ));

                    if (\count($publicationPks) >= self::CHUNK_SIZE_PUBLICATION) {
                        $this->flushPublications(
                            $context->getPortalNodeKey(),
                            $entityClassName,
                            $publicationPks
                        );

                        $publicationPks = [];
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
            if ($emissionPks !== []) {
                $this->flushDirectEmissions(
                    $emissionStack,
                    $emitContext,
                    $context->getPortalNodeKey(),
                    $entityClassName,
                    $emissionPks
                );
            }

            if ($publicationPks !== []) {
                $this->flushPublications(
                    $context->getPortalNodeKey(),
                    $entityClassName,
                    $publicationPks
                );
            }
        }
    }

    private function flushDirectEmissions(
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

        \iterable_filter($this->mappingService->getListByExternalIds($entityClassName, $portalNodeKey, $primaryKeys));
        $this->emissionActor->performEmission($primaryKeys, clone $emissionStack, $emitContext);
    }

    private function flushPublications(
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName,
        array $externalIds
    ): void {
        $this->logger->debug(\sprintf(
            'ExplorationActor: Flush a batch of publications. PortalNode: %s; Type: %s, PrimaryKeys: %s',
            $this->storageKeyGenerator->serialize($portalNodeKey),
            $entityClassName,
            \implode(',', $externalIds)
        ));

        $this->publisher->publishBatch(new MappingComponentCollection($this->iterableValues(\iterable_map(
            $this->mappingService->getListByExternalIds($entityClassName, $portalNodeKey, $externalIds),
            static fn (MappingInterface $mapping): MappingComponentStruct => new MappingComponentStruct($portalNodeKey, $entityClassName, $mapping->getExternalId())
        ))));
    }

    /**
     * @TODO replace with iterable_values from bentools v2
     */
    private function iterableValues(iterable $i): iterable
    {
        foreach ($i as $item) {
            yield $item;
        }
    }
}
