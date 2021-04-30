<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Exploration;

use Heptacom\HeptaConnect\Core\Exploration\Contract\ExplorerStackBuilderInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerContract;
use Heptacom\HeptaConnect\Portal\Base\Exploration\Contract\ExplorerStackInterface;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerStack;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ExplorerStackBuilder implements ExplorerStackBuilderInterface
{
    private PortalRegistryInterface $portalRegistry;

    private PortalNodeKeyInterface $portalNodeKey;

    private LoggerInterface $logger;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    private string $entityClassName;

    /**
     * @var ExplorerContract[]
     */
    private array $explorers = [];

    private ?PortalContract $cachedPortal = null;

    private ?PortalExtensionCollection $cachedPortalExtensions = null;

    /**
     * @param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $entityClassName
     */
    public function __construct(
        PortalRegistryInterface $portalRegistry,
        LoggerInterface $logger,
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->logger = $logger;
        $this->portalNodeKey = $portalNodeKey;
        $this->entityClassName = $entityClassName;
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

        foreach ($this->getPortal()->getExplorers()->bySupport($this->entityClassName) as $explorer) {
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
        foreach ($this->getPortalExtensions()->getExplorerDecorators()->bySupport($this->entityClassName) as $explorer) {
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

    private function getPortal(): PortalContract
    {
        return $this->cachedPortal ??= $this->portalRegistry->getPortal($this->portalNodeKey);
    }

    private function getPortalExtensions(): PortalExtensionCollection
    {
        return $this->cachedPortalExtensions ??= $this->portalRegistry->getPortalExtensions($this->portalNodeKey);
    }
}
