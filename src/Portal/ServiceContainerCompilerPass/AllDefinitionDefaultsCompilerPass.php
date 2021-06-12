<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AllDefinitionDefaultsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
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
