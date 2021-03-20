<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
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

    private StorageKeyGeneratorContract $storageKeyGenerator;

    /**
     * @var array<array-key, EmitterStackBuilderInterface>
     */
    private array $emitterStackBuilderCache = [];

    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    public function __construct(
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
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
            $stackBuilder = $this->getEmitterStacks($portalNodeKey, $entityClassName);

            if ($stackBuilder->isEmpty()) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

            $stack = $stackBuilder->build();

            try {
                /** @var MappedDatasetEntityStruct $mappedDatasetEntityStruct */
                foreach ($stack->next($mappingsForPortalNode, $this->emitContext) as $mappedDatasetEntityStruct) {
                    $this->messageBus->dispatch(new EmitMessage($mappedDatasetEntityStruct));
                }
            } catch (\Throwable $exception) {
                $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                    'stack' => $stack,
                    'exception' => $exception,
                ]);
            }
        }
    }

    private function getEmitterStacks(PortalNodeKeyInterface $portalNodeKey, string $entityClassName): EmitterStackBuilderInterface
    {
        $cacheKey = \md5(\join([$this->storageKeyGenerator->serialize($portalNodeKey), $entityClassName]));

        return $this->emitterStackBuilderCache[$cacheKey] ??= $this->emitterStackBuilderFactory
            ->createEmitterStackBuilder($portalNodeKey, $entityClassName)
            ->pushSource()
            ->pushDecorators();
    }
}
