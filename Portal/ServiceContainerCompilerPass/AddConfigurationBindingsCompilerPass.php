<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Symfony\Component\DependencyInjection\Argument\BoundArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddConfigurationBindingsCompilerPass implements CompilerPassInterface
{
    public const CONFIG_KEY_SEPARATORS = '_.-';

    public function process(ContainerBuilder $container): void
    {
        $keys = [];

        foreach (\array_keys($container->getParameterBag()->all()) as $key) {
            if (\is_string($key) && \str_starts_with($key, PortalStackServiceContainerBuilder::PORTAL_CONFIGURATION_PARAMETER_PREFIX)) {
                $keys[] = \mb_substr($key, \mb_strlen(PortalStackServiceContainerBuilder::PORTAL_CONFIGURATION_PARAMETER_PREFIX));
            }
        }

        $bindings = \array_combine(
            \array_map([$this, 'getBindingKey'], $keys),
            \array_map([$this, 'createBinding'], $keys)
        );

        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag(PortalStackServiceContainerBuilder::SERVICE_FROM_A_PORTAL_TAG)) {
                continue;
            }

            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            if (!\class_exists($class)) {
                continue;
            }

            /** @var array{string, array}[] $methodCalls */
            $methodCalls = $definition->getMethodCalls();
            $argumentNames = \array_merge(
                $this->getConstructorArgumentNames($class),
                $this->getConstructorCallNames($class, $methodCalls),
            );
            $related = \array_filter($argumentNames, static fn (string $key): bool => \str_starts_with($key, '$config'));
            $requiredBindings = \array_intersect_key($bindings, \array_flip($related));

            $definition->setBindings($requiredBindings);
        }
    }

    private function getParameterKey(string $configurationName): string
    {
        return PortalStackServiceContainerBuilder::PORTAL_CONFIGURATION_PARAMETER_PREFIX . $configurationName;
    }

    private function getBindingKey(string $configurationName): string
    {
        return '$config' . \str_replace(\str_split(self::CONFIG_KEY_SEPARATORS), '', \ucwords($configurationName, self::CONFIG_KEY_SEPARATORS));
    }

    private function createBinding(string $configurationName): BoundArgument
    {
        return new BoundArgument('%' . $this->getParameterKey($configurationName) . '%');
    }

    /**
     * @param class-string $class
     */
    private function getConstructorArgumentNames(string $class): array
    {
        return $this->extractParameterParameterNames((new \ReflectionClass($class))->getConstructor());
    }

    /**
     * @param class-string $class
     * @param array{string, array}[] $methodCalls
     */
    private function getConstructorCallNames(string $class, array $methodCalls): array
    {
        $result = [];

        foreach ($methodCalls as [$method]) {
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

    /**
     * @return string[]
     */
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
