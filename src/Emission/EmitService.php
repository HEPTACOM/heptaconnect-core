<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Component\LogMessage;
use Heptacom\HeptaConnect\Core\Component\Messenger\Message\EmitMessage;
use Heptacom\HeptaConnect\Core\Emission\Contract\EmitServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\TypedMappingCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EmitService implements EmitServiceInterface
{
    private EmitContextInterface $emitContext;

    private LoggerInterface $logger;

    private MessageBusInterface $messageBus;

    private PortalRegistryInterface $portalRegistry;

    public function __construct(
        EmitContextInterface $emitContext,
        LoggerInterface $logger,
        MessageBusInterface $messageBus,
        PortalRegistryInterface $portalRegistry
    ) {
        $this->emitContext = $emitContext;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->portalRegistry = $portalRegistry;
    }

    public function emit(TypedMappingCollection $mappings): void
    {
        $emittingPortalNodes = [];
        $entityClassName = $mappings->getType();

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $portalNodeKey = $mapping->getPortalNodeKey();

            if (\array_reduce($emittingPortalNodes, static function (bool $match, PortalNodeKeyInterface $key) use ($portalNodeKey): bool {
                return $match || $key->equals($portalNodeKey);
            }, false)) {
                continue;
            }

            $portal = $this->portalRegistry->getPortal($portalNodeKey);
            if (!$portal instanceof PortalContract) {
                continue;
            }

            $portalExtensions = $this->portalRegistry->getPortalExtensions($portalNodeKey);
            $emitters = $portal->getEmitters()->bySupport($entityClassName);
            $emittingPortalNodes[] = $portalNodeKey;
            $mappingsIterator = $mappings->filter(static function (MappingInterface $mapping) use ($portalNodeKey): bool {
                return $mapping->getPortalNodeKey()->equals($portalNodeKey);
            });
            /** @psalm-var array<array-key, \Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface> $mappingsForPortalNode */
            $mappingsForPortalNode = iterable_to_array($mappingsIterator);
            $mappingsForPortalNode = new TypedMappingCollection($entityClassName, $mappingsForPortalNode);

            $hasEmitters = false;

            /** @var EmitterInterface $emitter */
            foreach ($emitters as $emitter) {
                $hasEmitters = true;
                $stack = new EmitterStack([
                    ...$portalExtensions->getEmitterDecorators()->bySupport($entityClassName),
                    $emitter,
                ]);

                try {
                    foreach ($stack->next($mappingsForPortalNode, $this->emitContext) as $mappedDatasetEntityStruct) {
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
                    'portalNodeKey' => $portalNodeKey,
                ]);
            }
        }
    }
}
