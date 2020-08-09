<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;

class WebhookContextFactory
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
    }

    public function createContext(WebhookInterface $webhook): WebhookContextInterface
    {
        return new WebhookContext($this->configurationService, $this->portalRegistry, $webhook);
    }
}
