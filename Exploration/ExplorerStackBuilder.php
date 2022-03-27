<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Psr\Log\LoggerInterface;

final class ExplorerStackBuilder implements ExplorerStackBuilderInterface
{
    private ?ExplorerContract $source;

    private ExplorerCollection $decorators;

    /**
     * @var class-string<DatasetEntityContract>
     */
    private string $entityType;

    private LoggerInterface $logger;

    /**
     * @var ExplorerContract[]
     */
    private array $explorers = [];

    /**
     * @param class-string<DatasetEntityContract> $entityType
     */
    public function __construct(
        ExplorerCollection $sources,
        string $entityType,
        LoggerInterface $logger
    ) {
        $sources = new ExplorerCollection($sources->bySupport($entityType));
        $this->source = $sources->shift();
        $this->decorators = $sources;
        $this->entityType = $entityType;
        $this->logger = $logger;
    }

    public function push(ExplorerContract $explorer): self
    {
        if (\is_a($this->entityType, $explorer->supports(), true)) {
            $this->logger->debug('ExplorerStackBuilder: Pushed an arbitrary explorer.', [
                'explorer' => $explorer,
            ]);

            $this->explorers[] = $explorer;
        } else {
            $this->logger->debug(
                \sprintf(
                    'ExplorerStackBuilder: Tried to push an arbitrary explorer, but it does not support type %s.',
                    $this->entityType,
                ),
                [
                    'explorer' => $explorer,
                ]
            );
        }

        return $this;
    }

    public function pushSource(): self
    {
        if ($this->source instanceof ExplorerContract) {
            $this->logger->debug('ExplorerStackBuilder: Pushed the source explorer.', [
                'explorer' => $this->source,
            ]);

            if (!\in_array($this->source, $this->explorers, true)) {
                $this->explorers[] = $this->source;
            }
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->decorators as $explorer) {
            $this->logger->debug('ExplorerStackBuilder: Pushed a decorator explorer.', [
                'explorer' => $explorer,
            ]);

            if (!\in_array($explorer, $this->explorers, true)) {
                $this->explorers[] = $explorer;
            }
        }

        return $this;
    }

    public function build(): ExplorerStackInterface
    {
        $explorerStack = new ExplorerStack(\array_map(
            static fn (ExplorerContract $e) => clone $e,
            \array_reverse($this->explorers, false),
        ));
        $explorerStack->setLogger($this->logger);

        $this->logger->debug('ExplorerStackBuilder: Built explorer stack.');

        return $explorerStack;
    }

    public function isEmpty(): bool
    {
        return $this->explorers === [];
    }
}
