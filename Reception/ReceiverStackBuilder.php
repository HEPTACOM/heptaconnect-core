<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Psr\Log\LoggerInterface;

final class ReceiverStackBuilder implements ReceiverStackBuilderInterface
{
    private ?ReceiverContract $source;

    private ReceiverCollection $decorators;

    private LoggerInterface $logger;

    private EntityType $entityType;

    /**
     * @var ReceiverContract[]
     */
    private array $receivers = [];

    public function __construct(
        ReceiverCollection $sources,
        EntityType $entityType,
        LoggerInterface $logger
    ) {
        $sources = new ReceiverCollection($sources->bySupport($entityType));
        $this->source = $sources->shift();
        $this->decorators = $sources;
        $this->entityType = $entityType;
        $this->logger = $logger;
    }

    public function push(ReceiverContract $receiver): self
    {
        if ($this->entityType->equals($receiver->getSupportedEntityType())) {
            $this->logger->debug('ReceiverStackBuilder: Pushed an arbitrary receiver.', [
                'receiver' => $receiver,
            ]);

            $this->receivers[] = $receiver;
        } else {
            $this->logger->debug(
                \sprintf(
                    'ReceiverStackBuilder: Tried to push an arbitrary receiver, but it does not support type %s.',
                    $this->entityType,
                ),
                [
                    'receiver' => $receiver,
                ]
            );
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof ReceiverContract) {
            $this->logger->debug('ReceiverStackBuilder: Pushed the source receiver.', [
                'receiver' => $this->source,
            ]);

            if (!\in_array($this->source, $this->receivers, true)) {
                $this->receivers[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $receiver) {
            $this->logger->debug('ReceiverStackBuilder: Pushed a decorator receiver.', [
                'receiver' => $receiver,
            ]);

            if (!\in_array($receiver, $this->receivers, true)) {
                $this->receivers[] = $receiver;
            }
        }

        return $this;
    }

    public function build(): ReceiverStackInterface
    {
        $receiverStack = new ReceiverStack(\array_map(
            static fn (ReceiverContract $receiver): ReceiverContract => clone $receiver,
            \array_reverse($this->receivers, false),
        ));
        $receiverStack->setLogger($this->logger);

        $this->logger->debug('ReceiverStackBuilder: Built receiver stack.');

        return $receiverStack;
    }

    public function isEmpty(): bool
    {
        return $this->receivers === [];
    }
}
