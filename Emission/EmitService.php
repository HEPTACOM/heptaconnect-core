<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionFlowEmittersFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitContextFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderFactoryInterface;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackProcessorInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingComponentCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Psr\Log\LoggerInterface;

final class EmitService implements EmitServiceInterface
{
    /**
     * @var array<array-key, EmitterStackInterface|null>
     */
    private array $emissionStackCache = [];

    /**
     * @var array<array-key, EmitContextInterface>
     */
    private array $emitContextCache = [];

    public function __construct(
        private EmitContextFactoryInterface $emitContextFactory,
        private LoggerInterface $logger,
        private StorageKeyGeneratorContract $storageKeyGenerator,
        private EmitterStackBuilderFactoryInterface $emitterStackBuilderFactory,
        private EmissionFlowEmittersFactoryInterface $emissionFlowEmittersFactory,
        private EmitterStackProcessorInterface $stackProcessor
    ) {
    }

    public function emit(TypedMappingComponentCollection $mappingComponents): void
    {
        $emittingPortalNodes = [];
        $entityType = $mappingComponents->getEntityType();

        /** @var MappingComponentStructContract $mapping */
        foreach ($mappingComponents as $mapping) {
            $portalNodeKey = $mapping->getPortalNodeKey();

            // TODO: Group mapping components by portal node and iterate over the chunks for less complexity.
            if (\array_reduce($emittingPortalNodes, fn (bool $match, PortalNodeKeyInterface $key) => $match || $key->equals($portalNodeKey), false)) {
                continue;
            }

            $emittingPortalNodes[] = $portalNodeKey;
            $stack = $this->getEmitterStack($portalNodeKey, $entityType);

            if (!$stack instanceof EmitterStackInterface) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), [
                    'type' => $entityType,
                    'portalNodeKey' => $portalNodeKey,
                ]);

                continue;
            }

            $externalIds = (new MappingComponentCollection($mappingComponents->filterByPortalNodeKey($portalNodeKey)))->getExternalIds();

            $this->stackProcessor->processStack($externalIds, $stack, $this->getEmitContext($portalNodeKey));
        }
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    private function getEmitterStack(PortalNodeKeyInterface $portalNodeKey, EntityType $entityType): ?EmitterStackInterface
    {
        $cacheKey = \implode('', [$this->storageKeyGenerator->serialize($portalNodeKey), $entityType]);

        if (!\array_key_exists($cacheKey, $this->emissionStackCache)) {
            $builder = $this->emitterStackBuilderFactory
                ->createEmitterStackBuilder($portalNodeKey, $entityType)
                ->pushSource();

            if ($builder->isEmpty()) {
                $this->emissionStackCache[$cacheKey] = null;

                return null;
            }

            $builder->pushDecorators();

            foreach ($this->emissionFlowEmittersFactory->createEmitters($portalNodeKey, $entityType) as $emitter) {
                $builder = $builder->push($emitter);
            }

            $this->emissionStackCache[$cacheKey] = $builder->build();
        }

        $result = $this->emissionStackCache[$cacheKey];

        if ($result instanceof EmitterStackInterface) {
            return clone $result;
        }

        return null;
    }

    private function getEmitContext(PortalNodeKeyInterface $portalNodeKey): EmitContextInterface
    {
        $cacheKey = $this->storageKeyGenerator->serialize($portalNodeKey);
        $this->emitContextCache[$cacheKey] ??= $this->emitContextFactory->createContext($portalNodeKey);

        return clone $this->emitContextCache[$cacheKey];
    }
}
