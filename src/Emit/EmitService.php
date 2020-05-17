<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emit;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emit\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalNodeRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Contract\PortalNodeInterface;
use Heptacom\HeptaConnect\Portal\Base\TypedMappingCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EmitService implements EmitServiceInterface
{
    private EmitContextInterface $emitContext;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private PortalNodeRegistryInterface $portalNodeRegistry;

    public function __construct(
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        PortalNodeRegistryInterface $portalNodeRegistry
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->portalNodeRegistry = $portalNodeRegistry;
    }

    public function emit(TypedMappingCollection $mappings): void
    {
        $emittingPortalNodes = [];
        $entityClassName = $mappings->getType();

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $portalNodeId = $mapping->getPortalNodeId();

            if (isset($emittingPortalNodes[$portalNodeId])) {
                continue;
            }

            $portalNode = $this->portalNodeRegistry->getPortalNode($portalNodeId);
            if (!$portalNode instanceof PortalNodeInterface) {
                continue;
            }

            $emittingPortalNodes[$portalNodeId] = $portalNode->getEmitters()->bySupport($entityClassName);
        }

        foreach ($emittingPortalNodes as $portalNodeId => $emitters) {
            $mappingsIterator = $mappings->filter(function (MappingInterface $mapping) use ($portalNodeId): bool {
                return $mapping->getPortalNodeId() === $portalNodeId;
            });

            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Contract\MappingInterface> $mappingsForPortalNode */
            $mappingsForPortalNode = iterable_to_array($mappingsIterator);
            $mappingsForPortalNode = new TypedMappingCollection($entityClassName, $mappingsForPortalNode);

            $hasEmitters = false;

            /** @var EmitterInterface $emitter */
            foreach ($emitters as $emitter) {
                $hasEmitters = true;

                try {
                    foreach ($emitter->emit($mappingsForPortalNode, $this->emitContext) as $mappedDatasetEntityStruct) {
                        $this->messageBus->dispatch(new EmitMessage($mappedDatasetEntityStruct));
                    }
                } catch (\Throwable $exception) {
                    $this->logger->critical(LogMessage::EMIT_NO_THROW(), [
                        'type' => $entityClassName,
                        'emitter' => \get_class($emitter),
                        'exception' => $exception,
                    ]);
                }
            }

            if (!$hasEmitters) {
                $this->logger->critical(LogMessage::EMIT_NO_EMITTER_FOR_TYPE(), [
                    'type' => $entityClassName,
                    'portalNodeId' => $portalNodeId,
                ]);
            }
        }
    }
}
