<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal;

use Heptacom\HeptaConnect\Portal\Base\Builder\FlowComponent;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;

class FlowComponentRegistry
{
    private ?array $orderedSources = null;

    /**
     * @var array<class-string, ExplorerCollection>
     */
    private array $sourcedExplorers;

    /**
     * @var array<class-string, EmitterCollection>
     */
    private array $sourcedEmitters;

    /**
     * @var array<class-string, ReceiverCollection>
     */
    private array $sourcedReceivers;

    /**
     * @var array<class-string, StatusReporterCollection>
     */
    private array $sourcedStatusReporters;

    /**
     * @var array<class-string, HttpHandlerCollection>
     */
    private array $sourcedWebHttpHandlers;

    /**
     * @var array<class-string, string[]>
     */
    private array $flowBuilderFiles;

    /**
     * @param array<class-string, ExplorerCollection>       $sourcedExplorers
     * @param array<class-string, EmitterCollection>        $sourcedEmitters
     * @param array<class-string, ReceiverCollection>       $sourcedReceivers
     * @param array<class-string, StatusReporterCollection> $sourcedStatusReporters
     * @param array<class-string, HttpHandlerCollection>    $sourcedWebHttpHandlers
     * @param array<class-string, string[]>                 $flowBuilderFiles
     */
    public function __construct(
        array $sourcedExplorers,
        array $sourcedEmitters,
        array $sourcedReceivers,
        array $sourcedStatusReporters,
        array $sourcedWebHttpHandlers,
        array $flowBuilderFiles
    ) {
        $this->sourcedExplorers = $sourcedExplorers;
        $this->sourcedEmitters = $sourcedEmitters;
        $this->sourcedReceivers = $sourcedReceivers;
        $this->sourcedStatusReporters = $sourcedStatusReporters;
        $this->sourcedWebHttpHandlers = $sourcedWebHttpHandlers;
        $this->flowBuilderFiles = $flowBuilderFiles;
    }

    public function getExplorers(string $source): ExplorerCollection
    {
        $this->loadSource($source);

        return new ExplorerCollection($this->sourcedExplorers[$source] ?? []);
    }

    public function getEmitters(string $source): EmitterCollection
    {
        $this->loadSource($source);

        return new EmitterCollection($this->sourcedEmitters[$source] ?? []);
    }

    public function getReceivers(string $source): ReceiverCollection
    {
        $this->loadSource($source);

        return new ReceiverCollection($this->sourcedReceivers[$source] ?? []);
    }

    public function getStatusReporters(string $source): StatusReporterCollection
    {
        $this->loadSource($source);

        return new StatusReporterCollection($this->sourcedStatusReporters[$source] ?? []);
    }

    public function getWebHttpHandlers(string $source): HttpHandlerCollection
    {
        $this->loadSource($source);

        return new HttpHandlerCollection($this->sourcedWebHttpHandlers[$source] ?? []);
    }

    /**
     * Returns an ordered array of FQCNs for the portal class and all supporting portal extension classes that
     * contribute flow components to the current portal container. The first item will be the FQCN of the portal class.
     * The supporting portal extension FQCNs will be ordered lexicographically.
     *
     * @return class-string[]
     */
    public function getOrderedSources(): array
    {
        $result = $this->orderedSources;

        if ($result === null) {
            $result = \array_unique([
                ...\array_keys($this->sourcedExplorers),
                ...\array_keys($this->sourcedEmitters),
                ...\array_keys($this->sourcedReceivers),
                ...\array_keys($this->sourcedStatusReporters),
                ...\array_keys($this->sourcedWebHttpHandlers),
                ...\array_keys($this->flowBuilderFiles),
            ]);
            \usort($result, static function (string $portalClassA, string $portalClassB): int {
                $aT = (int) \is_a($portalClassA, PortalContract::class, true);
                $bT = (int) \is_a($portalClassB, PortalContract::class, true);

                if ($aT === $bT) {
                    return \strcmp($portalClassA, $portalClassB);
                }

                return $bT <=> $aT;
            });

            $this->orderedSources = $result;
        }

        return $result;
    }

    private function loadSource(string $source): void
    {
        $files = $this->flowBuilderFiles[$source] ?? [];

        if ($files !== []) {
            $flowBuilder = new FlowComponent();

            $flowBuilder->reset();

            foreach ($files as $file) {
                // prevent access to object context
                (static function (string $file): void {
                    include $file;
                })($file);
            }

            ($this->sourcedExplorers[$source] ??= new ExplorerCollection())->push($flowBuilder->buildExplorers());
            ($this->sourcedEmitters[$source] ??= new EmitterCollection())->push($flowBuilder->buildEmitters());
            ($this->sourcedReceivers[$source] ??= new ReceiverCollection())->push($flowBuilder->buildReceivers());
            ($this->sourcedStatusReporters[$source] ??= new StatusReporterCollection())->push($flowBuilder->buildStatusReporters());
            ($this->sourcedWebHttpHandlers[$source] ??= new HttpHandlerCollection())->push($flowBuilder->buildHttpHandlers());

            unset($this->flowBuilderFiles[$source]);
        }
    }
}
