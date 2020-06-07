<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Webhook\Contract;

interface UrlProviderInterface
{
    public function provide(): string;
}
