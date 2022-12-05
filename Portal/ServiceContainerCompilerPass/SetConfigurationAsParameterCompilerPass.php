<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Portal\ServiceContainerCompilerPass;

use Heptacom\HeptaConnect\Core\Portal\PortalConfiguration;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SetConfigurationAsParameterCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private ?array $configuration
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $config = new PortalConfiguration($this->configuration ?? []);
        $keys = $config->keys();

        foreach (\array_keys($container->getParameterBag()->all()) as $parameterName) {
            if (\str_starts_with($parameterName, PortalStackServiceContainerBuilder::PORTAL_CONFIGURATION_PARAMETER_PREFIX)) {
                $container->getParameterBag()->remove($parameterName);
            }
        }

        foreach ($keys as $key) {
            $container->setParameter($this->getParameterKey($key), $config->get($key));
        }
    }

    private function getParameterKey(string $configurationName): string
    {
        return PortalStackServiceContainerBuilder::PORTAL_CONFIGURATION_PARAMETER_PREFIX . $configurationName;
    }
}
