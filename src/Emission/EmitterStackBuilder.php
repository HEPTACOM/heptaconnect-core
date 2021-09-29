<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Emission;

use Heptacom\HeptaConnect\Core\Emission\Contract\EmitterStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterContract;
use Heptacom\HeptaConnect\Portal\Base\Emission\Contract\EmitterStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class EmitterStackBuilder implements EmitterStackBuilderInterface
{
    private EmitterCollection $sourceEmitters;

    private EmitterCollection $emitterDecorators;

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
        EmitterCollection $sourceEmitters,
        EmitterCollection $emitterDecorators,
        string $entityType,
        LoggerInterface $logger
    ) {
        $this->sourceEmitters = $sourceEmitters;
        $this->emitterDecorators = $emitterDecorators;
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
        $lastEmitter = null;

        foreach ($this->sourceEmitters->bySupport($this->entityType) as $emitter) {
            $lastEmitter = $emitter;
        }

        if ($lastEmitter instanceof EmitterContract) {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Pushed %s as source emitter.',
                \get_class($lastEmitter)
            ));

            $this->emitters[] = $lastEmitter;
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->emitterDecorators->bySupport($this->entityType) as $emitter) {
            $this->logger->debug(\sprintf(
                'EmitterStackBuilder: Pushed %s as decorator emitter.',
                \get_class($emitter)
            ));

            $this->emitters[] = $emitter;
        }

        return $this;
    }

    public function build(): EmitterStackInterface
    {
        $emitterStack = new EmitterStack(\array_map(
            static fn (EmitterContract $e) => clone $e,
            \array_reverse($this->emitters, false),
        ), $this->entityType);

        if ($emitterStack instanceof LoggerAwareInterface) {
            $emitterStack->setLogger($this->logger);
        }

        $this->logger->debug('EmitterStackBuilder: Built emitter stack.');

        return $emitterStack;
    }

    public function isEmpty(): bool
    {
        return empty($this->emitters);
    }
}
