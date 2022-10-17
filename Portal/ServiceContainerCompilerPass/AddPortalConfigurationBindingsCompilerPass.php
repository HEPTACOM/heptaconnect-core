<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\ConfigurationContract;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AddPortalConfigurationBindingsCompilerPass implements CompilerPassInterface
{
    public const CONFIG_KEY_SEPARATORS = '_.-';

    public function __construct(private ConfigurationContract $configuration)
    {
    }

    public function process(ContainerBuilder $container): void
    {
        $keys = $this->configuration->keys();

        if ($keys === []) {
            return;
        }

        foreach ($keys as $key) {
            $container->setParameter($this->getParameterKey($key), $this->configuration->get($key));
        }

        $bindings = \array_combine(
            \array_map([$this, 'getBindingKey'], $keys),
            \array_map([$this, 'createBinding'], $keys)
        );

        if (!\is_array($bindings)) {
            throw new \LogicException('array_combine should not have return false', 1637433403);
        }

        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag(PortalStackServiceContainerBuilder::SERVICE_FROM_A_PORTAL_TAG)) {
                continue;
            }

            $argumentNames = \array_merge(
                $this->getConstructorArgumentNames($definition),
                $this->getConstructorCallNames($definition),
            );
            $related = \array_filter($argumentNames, static fn (string $key): bool => str_starts_with($key, '$config'));
            $requiredBindings = \array_intersect_key($bindings, \array_flip($related));

            $definition->setBindings($requiredBindings);
        }
    }

    private function getParameterKey(string $configurationName): string
    {
        return 'portal_config.' . $configurationName;
    }

    private function getBindingKey(string $configurationName): string
    {
        return '$config' . \str_replace(\str_split(self::CONFIG_KEY_SEPARATORS), '', \ucwords($configurationName, self::CONFIG_KEY_SEPARATORS));
    }

    private function createBinding(string $configurationName): BoundArgument
    {
        return new BoundArgument('%' . $this->getParameterKey($configurationName) . '%');
    }

    private function getConstructorArgumentNames(Definition $definition): array
    {
        $class = $definition->getClass();

        if ($class === null || !\class_exists($class)) {
            return [];
        }

        return $this->extractParameterParameterNames((new \ReflectionClass($class))->getConstructor());
    }

    private function getConstructorCallNames(Definition $definition): array
    {
        $class = $definition->getClass();

        if ($class === null || !\class_exists($class)) {
            return [];
        }

        $calls = $definition->getMethodCalls();

        if ($calls === []) {
            return [];
        }

        $result = [];

        foreach ($calls as [$method, $arguments]) {
            if (!\method_exists($class, $method)) {
                continue;
            }

            $result[] = $this->extractParameterParameterNames(new \ReflectionMethod($class, $method));
        }

        return \array_merge([], ...$result);
    }

    /**
     * @return string[]
     *
     * @psalm-return array<int, string>
     */
    private function extractParameterParameterNames(?\ReflectionMethod $method): array
    {
        if (!$method instanceof \ReflectionMethod) {
            return [];
        }

        $parameters = $method->getParameters();
        $parameters = \array_filter($parameters, [$this, 'isParameterScalarish']);

        return \array_map(static fn (\ReflectionParameter $param): string => '$' . $param->getName(), $parameters);
    }

    private function isParameterScalarish(\ReflectionParameter $parameter): bool
    {
        foreach ($this->getParameterTypes($parameter->getType()) as $type) {
            if (\class_exists($type)) {
                return false;
            }

            if (\in_array(\mb_strtolower($type), ['string', 'float', 'int', 'bool', 'array'], true)) {
                return true;
            }
        }

        return false;
    }

    private function getParameterTypes(?\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof \ReflectionUnionType) {
            return \array_merge([], ...\array_map([$this, 'getParameterTypes'], $type->getTypes()));
        }

        return [];
    }
}
