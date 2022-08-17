<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Psr\Log\LoggerInterface;

final class EmitterStackBuilder implements EmitterStackBuilderInterface
{
    private ?EmitterContract $source;

    private EmitterCollection $decorators;

    private EntityType $entityType;

    private LoggerInterface $logger;

    /**
     * @var EmitterContract[]
     */
    private array $emitters = [];

    public function __construct(
        EmitterCollection $sources,
        EntityType $entityType,
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
        if ($this->entityType->equals($emitter->getSupportedEntityType())) {
            $this->logger->debug('EmitterStackBuilder: Pushed an arbitrary emitter.', [
                'emitter' => $emitter,
            ]);

            $this->emitters[] = $emitter;
        } else {
            $this->logger->debug(
                \sprintf(
                    'EmitterStackBuilder: Tried to push an arbitrary emitter, but it does not support type %s.',
                    $this->entityType,
                ),
                [
                    'emitter' => $emitter,
                ]
            );
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof EmitterContract) {
            $this->logger->debug('EmitterStackBuilder: Pushed the source emitter.', [
                'emitter' => $this->source,
            ]);

            if (!\in_array($this->source, $this->emitters, true)) {
                $this->emitters[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $emitter) {
            $this->logger->debug('EmitterStackBuilder: Pushed a decorator emitter.', [
                'emitter' => $emitter,
            ]);

            if (!\in_array($emitter, $this->emitters, true)) {
                $this->emitters[] = $emitter;
            }
        }

        return $this;
    }

    public function build(): EmitterStackInterface
    {
        $emitterStack = new EmitterStack(\array_map(
            static fn (EmitterContract $emitter): EmitterContract => clone $emitter,
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
