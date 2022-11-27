<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Portal\FlowComponentRegistry;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class BuildDefinitionForFlowComponentRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * @param array<string, string[]> $flowBuilderFiles
     */
    public function __construct(
        private array $flowBuilderFiles
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $groupedExplorers = $this->getServiceReferencesGroupedBySource($container, PortalStackServiceContainerBuilder::EXPLORER_SOURCE_TAG);
        $groupedEmitters = $this->getServiceReferencesGroupedBySource($container, PortalStackServiceContainerBuilder::EMITTER_SOURCE_TAG);
        $groupedReceivers = $this->getServiceReferencesGroupedBySource($container, PortalStackServiceContainerBuilder::RECEIVER_SOURCE_TAG);
        $groupedStatusReporters = $this->getServiceReferencesGroupedBySource($container, PortalStackServiceContainerBuilder::STATUS_REPORTER_SOURCE_TAG);
        $groupedWebHttpHandlers = $this->getServiceReferencesGroupedBySource($container, PortalStackServiceContainerBuilder::WEB_HTTP_HANDLER_SOURCE_TAG);

        $container->setDefinition(FlowComponentRegistry::class, (new Definition(FlowComponentRegistry::class))->setArguments([
            $this->groupServices(ExplorerCollection::class, $groupedExplorers),
            $this->groupServices(EmitterCollection::class, $groupedEmitters),
            $this->groupServices(ReceiverCollection::class, $groupedReceivers),
            $this->groupServices(StatusReporterCollection::class, $groupedStatusReporters),
            $this->groupServices(HttpHandlerCollection::class, $groupedWebHttpHandlers),
            $this->flowBuilderFiles,
        ]));
    }

    /**
     * @return Definition[]
     *
     * @psalm-return array<Definition>
     */
    private function groupServices(string $collectionClass, array $groupServiceIds): array
    {
        return \array_map(static fn (array $refs): Definition => (new Definition($collectionClass))->setArguments([$refs]), $groupServiceIds);
    }

    private function getServiceReferencesGroupedBySource(ContainerBuilder $container, string $tag): array
    {
        $grouped = [];
        $serviceIds = $container->findTaggedServiceIds($tag);

        foreach ($serviceIds as $serviceId => $tagData) {
            $groupKey = $tagData[0]['source'] ?? null;

            if ($groupKey !== null) {
                $grouped[$groupKey][] = new Reference($serviceId);
            }
        }

        return $grouped;
    }
}
