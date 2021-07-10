<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;

/**
 * @internal
 */
class WebhookContextFactory
{
    private ConfigurationServiceInterface $configurationService;

    private PortalStackServiceContainerFactory $portalStackServiceContainerFactory;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->configurationService = $configurationService;
        $this->portalStackServiceContainerFactory = $portalStackServiceContainerFactory;
    }

    public function createContext(WebhookInterface $webhook): WebhookContextInterface
    {
        return new WebhookContext(
            $this->portalStackServiceContainerFactory->create($webhook->getPortalNodeKey()),
            $this->configurationService->getPortalNodeConfiguration($webhook->getPortalNodeKey()),
            $webhook
        );
    }
}
