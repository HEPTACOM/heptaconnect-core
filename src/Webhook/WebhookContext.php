<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Webhook;

use Heptacom\HeptaConnect\Core\Portal\AbstractPortalNodeContext;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookContextInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;
use Psr\Container\ContainerInterface;

class WebhookContext extends AbstractPortalNodeContext implements WebhookContextInterface
{
    private WebhookInterface $webhook;

    public function __construct(ContainerInterface $container, ?array $configuration, WebhookInterface $webhook)
    {
        parent::__construct($container, $configuration);
        $this->webhook = $webhook;
    }

    public function getWebhook(): WebhookInterface
    {
        return $this->webhook;
    }
}
