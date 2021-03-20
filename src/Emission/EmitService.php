<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Psr\Log\LoggerInterface;

class EmitService implements EmitServiceInterface
{
    private EmitContextInterface $emitContext;

    private LoggerInterface $logger;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    /**
     * @var array<array-key, EmitterStackInterface>
     */
    private array $emissionStackCache = [];

    private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory;

    private EmissionActorInterface $emissionActor;

    public function __construct(
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        EmissionActorInterface $emissionActor
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->emitterStackBuilderFactory = $emitterStackBuilderFactory;
        $this->emissionActor = $emissionActor;
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
            $stackBuilder = $this->getEmitterStack($portalNodeKey, $entityClassName);

            if (!$stackBuilder instanceof EmitterStackInterface) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

            $mappingsIterator = $mappings->filter(fn (MappingInterface $mapping) => $mapping->getPortalNodeKey()->equals($portalNodeKey));
            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface> $mappingsForPortalNode */
            $mappingsForPortalNode = iterable_to_array($mappingsIterator);
            $mappingsForPortalNode = new TypedMappingCollection($entityClassName, $mappingsForPortalNode);

            $this->emissionActor->performEmission($mappingsForPortalNode, $stackBuilder, $this->emitContext);
        }
    }

    private function getEmitterStack(PortalNodeKeyInterface $portalNodeKey, string $entityClassName): ?EmitterStackInterface
    {
        $cacheKey = \join([$this->storageKeyGenerator->serialize($portalNodeKey), $entityClassName]);

        if (!\array_key_exists($cacheKey, $this->emissionStackCache)) {
            $builder = $this->emitterStackBuilderFactory
                ->createEmitterStackBuilder($portalNodeKey, $entityClassName)
                ->pushSource()
                // TODO break when source is already empty
                ->pushDecorators();

            $this->emissionStackCache[$cacheKey] = $builder->isEmpty() ? null : $builder->build();
        }

        $result = $this->emissionStackCache[$cacheKey];

        if ($result instanceof EmitterStackInterface) {
            return clone $result;
        }

        return null;
    }
}
