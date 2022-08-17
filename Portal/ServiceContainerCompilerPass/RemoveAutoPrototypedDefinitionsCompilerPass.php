<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Dataset\Base\Contract\AttachableInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\CollectionInterface;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final class RemoveAutoPrototypedDefinitionsCompilerPass implements CompilerPassInterface
{
    private array $prototypedIds;

    public function __construct(array $prototypedIds)
    {
        $this->prototypedIds = $prototypedIds;
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
    }

    protected function isPrototypable(string $class): bool
    {
        if (!\class_exists($class)) {
            return false;
        }

        if (\is_a($class, \Throwable::class, true)) {
            return false;
        }

        if (\is_a($class, DatasetEntityContract::class, true)) {
            return false;
        }

        if (\is_a($class, CollectionInterface::class, true)) {
            return false;
        }

        if (\is_a($class, AttachableInterface::class, true)) {
            return false;
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
