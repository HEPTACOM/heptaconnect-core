<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ExplorerStackBuilder implements ExplorerStackBuilderInterface
{
    private ExplorerCollection $sourceExplorers;

    private ExplorerCollection $explorerDecorators;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityClassName;

    private LoggerInterface $logger;

    /**
     * @var ExplorerContract[]
     */
    private array $explorers = [];

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityClassName
     */
    public function __construct(
        ExplorerCollection $sourceExplorers,
        ExplorerCollection $explorerDecorators,
        string $entityClassName,
        LoggerInterface $logger
    ) {
        $this->sourceExplorers = $sourceExplorers;
        $this->explorerDecorators = $explorerDecorators;
        $this->entityClassName = $entityClassName;
        $this->logger = $logger;
    }

    public function push(ExplorerContract $explorer): self
    {
        if (\is_a($this->entityClassName, $explorer->supports(), true)) {
            $this->logger->debug(\sprintf(
                'ExplorerStackBuilder: Pushed %s as arbitrary explorer.',
                \get_class($explorer)
            ));

            $this->explorers[] = $explorer;
        } else {
            $this->logger->debug(\sprintf(
                'ExplorerStackBuilder: Tried to push %s as arbitrary explorer, but it does not support type %s.',
                \get_class($explorer),
                $this->entityClassName,
            ));
        }

        return $this;
    }

    public function pushSource(): self
    {
        $lastExplorer = null;

        foreach ($this->sourceExplorers->bySupport($this->entityClassName) as $explorer) {
            $lastExplorer = $explorer;
        }

        if ($lastExplorer instanceof ExplorerContract) {
            $this->logger->debug(\sprintf(
                'ExplorerStackBuilder: Pushed %s as source explorer.',
                \get_class($lastExplorer)
            ));

            $this->explorers[] = $lastExplorer;
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->explorerDecorators->bySupport($this->entityClassName) as $explorer) {
            $this->logger->debug(\sprintf(
                'ExplorerStackBuilder: Pushed %s as decorator explorer.',
                \get_class($explorer)
            ));

            $this->explorers[] = $explorer;
        }

        return $this;
    }

    public function build(): ExplorerStackInterface
    {
        $explorerStack = new ExplorerStack(\array_map(
            static fn (ExplorerContract $e) => clone $e,
            \array_reverse($this->explorers, false),
        ));

        if ($explorerStack instanceof LoggerAwareInterface) {
            $explorerStack->setLogger($this->logger);
        }

        $this->logger->debug('ExplorerStackBuilder: Built explorer stack.');

        return $explorerStack;
    }

    public function isEmpty(): bool
    {
        return empty($this->explorers);
    }
}
