<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class ResolvedPublicUrlFileReference extends ResolvedFileReferenceContract
{
    private string $publicUrl;

    private ClientInterface $client;

    private RequestFactoryInterface $requestFactory;

    public function __construct(
        string $publicUrl,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory
    ) {
        $this->publicUrl = $publicUrl;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getContents(): string
    {
        return $this->client->sendRequest(
            $this->requestFactory->createRequest('GET', $this->publicUrl)
        )->getBody()->getContents();
    }
}
