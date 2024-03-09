<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Portal\FlowComponentRegistry;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Heptacom\HeptaConnect\Portal\Base\Emission\EmitterCollection;
use Heptacom\HeptaConnect\Portal\Base\Exploration\ExplorerCollection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PackageContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\PackageCollection;
use Heptacom\HeptaConnect\Portal\Base\Reception\ReceiverCollection;
use Heptacom\HeptaConnect\Portal\Base\StatusReporting\StatusReporterCollection;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\HttpHandlerCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class BuildDefinitionForFlowComponentRegistryCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

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
            new Reference(PackageCollection::class),
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
        /** @var PackageCollection $packages */
        $packages = $container->get(PackageCollection::class);

        $grouped = [];
        $serviceIds = $this->findAndSortTaggedServices($tag, $container);

        foreach ($serviceIds as $reference) {
            $definition = $container->findDefinition((string) $reference);
            $tagData = $definition->getTag($tag);

            $priority = $tagData[0]['priority'] ?? null;

            if (!\is_int($priority)) {
                $source = $tagData[0]['source'] ?? null;

                if (!\is_string($source)) {
                    throw new \Exception(
                        'Tag "' . $tag . '" of service "' . $reference . '" is missing "source" attribute.',
                        1693671570
                    );
                }

                $sourcePackage = $this->getSourcePackage($packages, $source);
                $priority = $sourcePackage->getDefaultFlowComponentPriority();
            }

            $grouped[$priority][] = $reference;
        }

        \ksort($grouped);

        return $grouped;
    }

    private function getSourcePackage(PackageCollection $builtPackages, string $source): PackageContract
    {
        $packages = $builtPackages->withoutItems();

        $packages->push($builtPackages->filter(
            static fn (PackageContract $package): bool => \get_class($package) === $source
        ));

        $sourcePackage = $packages->first();

        if (!$sourcePackage instanceof PackageContract) {
            throw new \Exception(
                'Unable to find source package "' . $source . '" in built packages.',
                1693698154
            );
        }

        return $sourcePackage;
    }
}
