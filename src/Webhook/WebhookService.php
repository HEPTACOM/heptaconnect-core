<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Core\Webhook\Contract\UrlProviderInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookServiceInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageInterface;

class WebhookService implements WebhookServiceInterface
{
    private StorageInterface $storage;

    private UrlProviderInterface $urlProvider;

    public function __construct(StorageInterface $storage, UrlProviderInterface $urlProvider)
    {
        $this->storage = $storage;
        $this->urlProvider = $urlProvider;
    }

    public function register(PortalNodeKeyInterface $portalNodeKey, string $webhookHandler, ?array $payload = null): WebhookInterface
    {
        return $this->storage->createWebhook(
            $portalNodeKey,
            $this->urlProvider->provide()->getPath(),
            $webhookHandler,
            $payload
        );
    }

    public function scheduleRefresh(WebhookInterface $webhook, \DateTimeInterface $dateTime): void
    {
        // TODO: Implement scheduleRefresh() method.
    }
}
