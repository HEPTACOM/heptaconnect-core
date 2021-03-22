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

class ExplorerStackBuilder implements ExplorerStackBuilderInterface
{
    private PortalRegistryInterface $portalRegistry;

    private PortalNodeKeyInterface $portalNodeKey;

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
        PortalNodeKeyInterface $portalNodeKey,
        string $entityClassName
    ) {
        $this->portalRegistry = $portalRegistry;
        $this->portalNodeKey = $portalNodeKey;
        $this->entityClassName = $entityClassName;
    }

    public function push(ExplorerContract $explorer): self
    {
        // TODO add supports check
        $this->explorers[] = $explorer;

        return $this;
    }

    public function pushSource(): self
    {
        $lastExplorer = null;

        foreach ($this->getPortal()->getExplorers()->bySupport($this->entityClassName) as $explorer) {
            $lastExplorer = $explorer;
        }

        if ($lastExplorer instanceof ExplorerContract) {
            $this->explorers[] = $lastExplorer;
        }

        return $this;
    }

    public function pushDecorators(): self
    {
        foreach ($this->getPortalExtensions()->getExplorerDecorators()->bySupport($this->entityClassName) as $explorer) {
            $this->explorers[] = $explorer;
        }

        return $this;
    }

    public function build(): ExplorerStackInterface
    {
        return new ExplorerStack(\array_map(
            static fn (ExplorerContract $e) => clone $e,
            \array_reverse($this->explorers, false),
        ));
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
