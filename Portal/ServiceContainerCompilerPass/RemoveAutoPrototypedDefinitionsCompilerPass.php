<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final class RemoveAutoPrototypedDefinitionsCompilerPass implements CompilerPassInterface
{
    /**
     * @param class-string[] $excludedClasses
     */
    public function __construct(
        private array $prototypedIds,
        private array $excludedClasses
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->prototypedIds as $serviceId) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass() ?? $serviceId;

            if ($this->isPrototypable($class)) {
                continue;
            }

            $container->removeDefinition($serviceId);
        }

        foreach ($this->excludedClasses as $aliasId) {
            if ($container->hasAlias($aliasId)) {
                $container->removeAlias($aliasId);
            }
        }
    }

    private function isPrototypable(string $class): bool
    {
        if (!\class_exists($class)) {
            return false;
        }

        foreach ($this->excludedClasses as $excludedClass) {
            if (\is_a($class, $excludedClass, true)) {
                return false;
            }
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return false;
        }

        $ctor = $reflection->getConstructor();

        if ($ctor === null) {
            return true;
        }

        if (!$ctor->isPublic() || $ctor->isAbstract()) {
            return false;
        }

        return true;
    }
}
