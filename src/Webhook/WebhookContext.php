<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Core\Configuration\Contract\ConfigurationServiceInterface;
use Heptacom\HeptaConnect\Core\Portal\Contract\PortalRegistryInterface;
use Heptacom\HeptaConnect\Core\Portal\PortalStorageFactory;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalStorageInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;

class WebhookContext implements WebhookContextInterface
{
    private ConfigurationServiceInterface $configurationService;

    private PortalRegistryInterface $portalRegistry;

    private PortalStorageFactory $portalStorageFactory;

    private WebhookInterface $webhook;

    public function __construct(
        ConfigurationServiceInterface $configurationService,
        PortalRegistryInterface $portalRegistry,
        PortalStorageFactory $portalStorageFactory,
        WebhookInterface $webhook
    ) {
        $this->configurationService = $configurationService;
        $this->portalRegistry = $portalRegistry;
        $this->portalStorageFactory = $portalStorageFactory;
        $this->webhook = $webhook;
    }

    public function getPortal(): PortalContract
    {
        return $this->portalRegistry->getPortal($this->webhook->getPortalNodeKey());
    }

    public function getConfig(): ?array
    {
        return $this->configurationService->getPortalNodeConfiguration($this->webhook->getPortalNodeKey());
    }

    public function getWebhook(): WebhookInterface
    {
        return $this->webhook;
    }

    public function getStorage(): PortalStorageInterface
    {
        return $this->portalStorageFactory->createPortalStorage($this->webhook->getPortalNodeKey());
    }
}
