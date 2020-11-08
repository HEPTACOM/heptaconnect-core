<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\Support\DatasetEntityTracker;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EmitService implements EmitServiceInterface
{
    private EmitContextInterface $emitContext;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private PortalRegistryInterface $portalRegistry;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private array $emitterStackCache = [];

    private MappingServiceInterface $mappingService;

    public function __construct(
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        PortalRegistryInterface $portalRegistry,
        StorageKeyGeneratorContract $storageKeyGenerator,
        MappingServiceInterface $mappingService
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->portalRegistry = $portalRegistry;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingService = $mappingService;
    }

    public function emit(TypedMappingCollection $mappings): void
    {
        $emittingPortalNodes = [];
        $entityClassName = $mappings->getType();

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $portalNodeKey = $mapping->getPortalNodeKey();

            if (\array_reduce($emittingPortalNodes, fn (bool $match, PortalNodeKeyInterface $key) => $match || $key->equals($portalNodeKey), false)) {
                continue;
            }

            $emittingPortalNodes[] = $portalNodeKey;
            $mappingsIterator = $mappings->filter(fn (MappingInterface $mapping) => $mapping->getPortalNodeKey()->equals($portalNodeKey));
            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface> $mappingsForPortalNode */
            $mappingsForPortalNode = iterable_to_array($mappingsIterator);
            $mappingsForPortalNode = new TypedMappingCollection($entityClassName, $mappingsForPortalNode);

            try {
                $stacks = $this->getEmitterStacks($portalNodeKey, $entityClassName);
            } catch (\Throwable $exception) {
                $this->logger->critical(LogMessage::EMIT_NO_STACKS(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                    'exception' => $exception,
                ]);

                continue;
            }

            if (empty($stacks)) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

            /** @var EmitterStackInterface $stack */
            foreach ($stacks as $stack) {
                try {
                    DatasetEntityTracker::listen();

                    /** @var MappedDatasetEntityStruct $mappedDatasetEntityStruct */
                    foreach ($stack->next($mappingsForPortalNode, $this->emitContext) as $mappedDatasetEntityStruct) {
                        try {
                            $trackedEntities = DatasetEntityTracker::retrieve();
                            $mappingsToEnsure = new MappingComponentCollection();

                            /** @var DatasetEntityInterface $trackedEntity */
                            foreach ($trackedEntities->getIterator() as $trackedEntity) {
                                if (!$trackedEntity instanceof DatasetEntityInterface || $trackedEntity->getPrimaryKey() === null) {
                                    continue;
                                }

                                $mappingsToEnsure->push([new MappingComponentStruct(
                                    $portalNodeKey,
                                    \get_class($trackedEntity),
                                    $trackedEntity->getPrimaryKey()
                                )]);
                            }

                            $this->mappingService->ensurePersistence($mappingsToEnsure);
                            $this->messageBus->dispatch(new EmitMessage($mappedDatasetEntityStruct, $trackedEntities));
                        } finally {
                            DatasetEntityTracker::listen();
                        }
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                        'type' => $entityClassName,
                        'portalNodeKey' => $portalNodeKey,
                        'stack' => $stack,
                        'exception' => $exception,
                    ]);
                } finally {
                    DatasetEntityTracker::retrieve();
                }
            }

            unset($stacks);
        }
    }

    /**
     * @return array<array-key, \Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface>
     */
    private function getEmitterStacks(PortalNodeKeyInterface $portalNodeKey, string $entityClassName): array
    {
        $cacheKey = \md5(\join([$this->storageKeyGenerator->serialize($portalNodeKey), $entityClassName]));

        if (!isset($this->emitterStackCache[$cacheKey])) {
            $portal = $this->portalRegistry->getPortal($portalNodeKey);
            $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);
            $emitters = $portal->getEmitters()->bySupport($entityClassName);
            $emitterDecorators = $portalExtensions->getEmitterDecorators()->bySupport($entityClassName);

            foreach ($emitters as $emitter) {
                $this->emitterStackCache[$cacheKey][] = new EmitterStack([...$emitterDecorators, $emitter]);
            }
        }

        return \array_map(
            fn (EmitterStackInterface $emitterStack) => clone $emitterStack,
            $this->emitterStackCache[$cacheKey] ??= []
        );
    }
}
