<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmissionActorInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EmissionActor implements EmissionActorInterface
{
    /**
     * @deprecated extract message bus from core
     */
    private MessageBusInterface $messageBus;

    private LoggerInterface $logger;

    public function __construct(MessageBusInterface $messageBus, LoggerInterface $logger)
    {
        $this->messageBus = $messageBus;
        $this->logger = $logger;
    }

    public function performEmission(
        TypedMappingCollection $mappings,
        EmitterStackInterface $stack,
        EmitContextInterface $context
    ): void {
        if ($mappings->count() < 1) {
            return;
        }

        try {
            /** @var MappedDatasetEntityStruct $mappedDatasetEntityStruct */
            foreach ($stack->next($mappings, $context) as $mappedDatasetEntityStruct) {
                $this->messageBus->dispatch(new EmitMessage($mappedDatasetEntityStruct));
            }
        } catch (\Throwable $exception) {
            $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                'type' => $mappings->getType(),
                'stack' => $stack,
                'exception' => $exception,
            ]);
        }
    }
}
