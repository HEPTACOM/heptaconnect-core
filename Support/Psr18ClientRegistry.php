<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Support;

use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;

class Psr18ClientRegistry
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? Psr18ClientDiscovery::find();
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }
}
