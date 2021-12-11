<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Reception;

use Heptacom\HeptaConnect\Core\Reception\Contract\ReceiverStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\Contract\ReceiverStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverStack;
use Psr\Log\LoggerInterface;

class ReceiverStackBuilder implements ReceiverStackBuilderInterface
{
    private ?ReceiverContract $source;

    private ReceiverCollection $decorators;

    private LoggerInterface $logger;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityType;

    /**
     * @var ReceiverContract[]
     */
    private array $receivers = [];

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityType
     */
    public function __construct(
        ReceiverCollection $sources,
        string $entityType,
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
        if (\is_a($this->entityType, $receiver->supports(), true)) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as arbitrary receiver.',
                \get_class($receiver)
            ));

            $this->receivers[] = $receiver;
        } else {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Tried to push %s as arbitrary receiver, but it does not support type %s.',
                \get_class($receiver),
                $this->entityType,
            ));
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof ReceiverContract) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as source receiver.',
                \get_class($this->source)
            ));

            if (!\in_array($this->source, $this->receivers, true)) {
                $this->receivers[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $receiver) {
            $this->logger->debug(\sprintf(
                'ReceiverStackBuilder: Pushed %s as decorator receiver.',
                \get_class($receiver)
            ));

            if (!\in_array($receiver, $this->receivers, true)) {
                $this->receivers[] = $receiver;
            }
        }

        return $this;
    }

    public function build(): ReceiverStackInterface
    {
        $receiverStack = new ReceiverStack(\array_map(
            static fn (ReceiverContract $e) => clone $e,
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
