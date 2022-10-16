<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Support\HttpMiddlewareCollector;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AddHttpMiddlewareCollectorCompilerPass implements CompilerPassInterface
{
    public const SERVICE_TAG = 'heptaconnect.http.handler.middleware';

    public function process(ContainerBuilder $container): void
    {
        $definitions = $container->getDefinitions();

        foreach ($definitions as $id => $definition) {
            $class = $definition->getClass() ?? (string) $id;

            if (!\class_exists($class) || !\is_a($class, MiddlewareInterface::class, true)) {
                continue;
            }

            $definition->addTag(self::SERVICE_TAG);
        }

        $definition = (new Definition(HttpMiddlewareCollector::class))
            ->setArguments([
                new TaggedIteratorArgument(self::SERVICE_TAG),
            ]);

        $container->setDefinition(HttpMiddlewareCollector::class, $definition);
    }
}
