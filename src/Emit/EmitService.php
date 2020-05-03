<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Emit\Component\EmitResult;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitServiceInteface;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use Psr\Log\LoggerInterface;

class EmitService implements EmitServiceInteface
{
    private EmitContextInterface $emitContext;

    private MappingServiceInterface $mappingService;

    private EmitterRegistryInterface $emitterRegistry;

    private LoggerInterface $logger;

    public function __construct(
        EmitContextInterface $emitContext,
        MappingServiceInterface $mappingService,
        EmitterRegistryInterface $emitterRegistry,
        LoggerInterface $logger
    ) {
        $this->emitContext = $emitContext;
        $this->mappingService = $mappingService;
        $this->logger = $logger;
        $this->emitterRegistry = $emitterRegistry;
    }

    public function emit(MappingCollection $mappings): EmitResult
    {
        $mappingsByType = [];

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $mappingType = $this->mappingService->getDatasetEntityClassName($mapping);
            $mappingsByType[$mappingType] ??= new MappingCollection();
            $mappingsByType[$mappingType]->push($mapping);
        }

        $result = new EmitResult();

        foreach ($mappingsByType as $type => $typedMappings) {
            $emitters = $this->emitterRegistry->bySupport($type);

            if (empty($emitters)) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), ['type' => $type]);
                continue;
            }

            /** @var EmitterInterface $emitter */
            foreach ($emitters as $emitter) {
                try {
                    // TODO chunk
                    $result->push(...$emitter->emit($typedMappings, $this->emitContext));
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                        'type' => $type,
                        'emitter' => \get_class($emitter),
                        'exception' => $exception,
                    ]);
                }
            }
        }

        return $result;
    }
}
