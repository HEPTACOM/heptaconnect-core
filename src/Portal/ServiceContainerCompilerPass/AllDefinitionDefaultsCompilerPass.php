<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerAwareInterface;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AllDefinitionDefaultsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(ProfilerAwareInterface::class)->addMethodCall('setProfiler', [
            new Reference(ProfilerContract::class),
        ]);

        foreach ($container->getDefinitions() as $definition) {
            if (!\array_key_exists('public', $definition->getChanges())) {
                $definition->setPublic(true);
            }

            if (!\array_key_exists('autoconfigured', $definition->getChanges())) {
                $definition->setAutoconfigured(true);
            }

            if (!\array_key_exists('autowired', $definition->getChanges())) {
                $definition->setAutowired(true);
            }
        }
    }
}
