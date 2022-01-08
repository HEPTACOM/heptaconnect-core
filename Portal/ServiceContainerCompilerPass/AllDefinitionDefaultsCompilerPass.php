<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerAwareInterface;
use Heptacom\HeptaConnect\Portal\Base\Profiling\ProfilerContract;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AllDefinitionDefaultsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass() ?? (string) $id;

            if (!\array_key_exists('public', $definition->getChanges())) {
                $definition->setPublic(true);
            }

            if (!\array_key_exists('autoconfigured', $definition->getChanges())) {
                $definition->setAutoconfigured(true);
            }

            if (!\array_key_exists('autowired', $definition->getChanges())) {
                $definition->setAutowired(true);
            }

            if (\is_a($class, LoggerAwareInterface::class, true)) {
                $definition->addMethodCall('setLogger', [
                    new Reference(LoggerInterface::class),
                ]);
            }

            if (\is_a($class, ProfilerAwareInterface::class, true)) {
                $definition->addMethodCall('setProfiler', [
                    new Reference(ProfilerContract::class),
                ]);
            }
        }
    }
}
