<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Component\Webhook\Contract;

use Psr\Http\Message\UriInterface;

interface UrlProviderInterface
{
    public function provide(): UriInterface;
}
