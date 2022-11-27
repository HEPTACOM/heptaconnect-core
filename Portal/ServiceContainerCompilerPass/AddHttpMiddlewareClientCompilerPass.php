<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Web\Http\HttpMiddlewareClient;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientMiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class AddHttpMiddlewareClientCompilerPass implements CompilerPassInterface
{
    public const SERVICE_TAG = 'heptaconnect.http.client.middleware';

    public function process(ContainerBuilder $container): void
    {
        $definitions = $container->getDefinitions();

        foreach ($definitions as $id => $definition) {
            $class = $definition->getClass() ?? $id;

            if (!\class_exists($class) || !\is_a($class, HttpClientMiddlewareInterface::class, true)) {
                continue;
            }

            $definition->addTag(self::SERVICE_TAG);
        }

        $definition = (new Definition(HttpMiddlewareClient::class))
            ->setDecoratedService(ClientInterface::class)
            ->setArguments([
                new Reference(HttpMiddlewareClient::class . '.inner'),
                new TaggedIteratorArgument(self::SERVICE_TAG),
            ]);

        $container->setDefinition(HttpMiddlewareClient::class, $definition);
    }
}
