<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitServiceInteface;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitterRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\MappingCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EmitService implements EmitServiceInteface
{
    private EmitContextInterface $emitContext;

    private EmitterRegistryInterface $emitterRegistry;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    public function __construct(
        EmitContextInterface $emitContext,
        EmitterRegistryInterface $emitterRegistry,
        LoggerInterface $logger,
        MessageBusInterface $messageBus
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->emitterRegistry = $emitterRegistry;
        $this->messageBus = $messageBus;
    }

    public function emit(MappingCollection $mappings): void
    {
        $mappingsByType = [];

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $mappingType = $mapping->getDatasetEntityClassName();
            $mappingsByType[$mappingType] ??= new MappingCollection();
            $mappingsByType[$mappingType]->push($mapping);
        }

        foreach ($mappingsByType as $type => $typedMappings) {
            $emitters = $this->emitterRegistry->bySupport($type);

            if (empty($emitters)) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), ['type' => $type]);
                continue;
            }

            /** @var EmitterInterface $emitter */
            foreach ($emitters as $emitter) {
                try {
                    foreach ($emitter->emit($typedMappings, $this->emitContext) as $mappedDatasetEntityStruct) {
                        $this->messageBus->dispatch(new EmitMessage($mappedDatasetEntityStruct));
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                        'type' => $type,
                        'emitter' => \get_class($emitter),
                        'exception' => $exception,
                    ]);
                }
            }
        }
    }
}
