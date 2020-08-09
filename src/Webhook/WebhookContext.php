<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;

class WebhookContext implements WebhookContextInterface
{
    private WebhookInterface $webhook;

    public function __construct(WebhookInterface $webhook)
    {
        $this->webhook = $webhook;
    }

    public function getWebhook(): WebhookInterface
    {
        return $this->webhook;
    }
}
