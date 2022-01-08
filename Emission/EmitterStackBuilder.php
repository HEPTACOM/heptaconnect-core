<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Psr\Log\LoggerInterface;

class EmitterStackBuilder implements EmitterStackBuilderInterface
{
    private ?EmitterContract $source;

    private EmitterCollection $decorators;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityType;

    private LoggerInterface $logger;

    /**
     * @var EmitterContract[]
     */
    private array $emitters = [];

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityType
     */
    public function __construct(
        EmitterCollection $sources,
        string $entityType,
        LoggerInterface $logger
    ) {
        $sources = new EmitterCollection($sources->bySupport($entityType));
        $this->source = $sources->shift();
        $this->decorators = $sources;
        $this->entityType = $entityType;
        $this->logger = $logger;
    }

    public function push(EmitterContract $emitter): self
    {
        if (\is_a($this->entityType, $emitter->supports(), true)) {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Pushed %s as arbitrary emitter.',
                \get_class($emitter)
            ));

            $this->emitters[] = $emitter;
        } else {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Tried to push %s as arbitrary emitter, but it does not support type %s.',
                \get_class($emitter),
                $this->entityType,
            ));
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof EmitterContract) {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Pushed %s as source emitter.',
                \get_class($this->source)
            ));

            if (!\in_array($this->source, $this->emitters, true)) {
                $this->emitters[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $emitter) {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Pushed %s as decorator emitter.',
                \get_class($emitter)
            ));

            if (!\in_array($emitter, $this->emitters, true)) {
                $this->emitters[] = $emitter;
            }
        }

        return $this;
    }

    public function build(): EmitterStackInterface
    {
        $emitterStack = new EmitterStack(\array_map(
            static fn (EmitterContract $e) => clone $e,
            \array_reverse($this->emitters, false),
        ), $this->entityType);
        $emitterStack->setLogger($this->logger);

        $this->logger->debug('EmitterStackBuilder: Built emitter stack.');

        return $emitterStack;
    }

    public function isEmpty(): bool
    {
        return $this->emitters === [];
    }
}
