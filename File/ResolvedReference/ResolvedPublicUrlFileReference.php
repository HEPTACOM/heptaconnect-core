<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class ResolvedPublicUrlFileReference extends ResolvedFileReferenceContract
{
    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        private string $publicUrl,
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory
    ) {
        parent::__construct($portalNodeKey);
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getContents(): string
    {
        $request = $this->requestFactory->createRequest('GET', $this->publicUrl);
        $response = $this->client->sendRequest($request);

        return (string) $response->getBody();
    }
}
