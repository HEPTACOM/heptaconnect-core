<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Core\Bridge\File\FileRequestUrlProviderInterface;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

class ResolvedRequestFileReference extends ResolvedFileReferenceContract
{
    private FileReferenceRequestKeyInterface $requestId;

    private ClientInterface $client;

    private PortalNodeKeyInterface $portalNodeKey;

    private FileRequestUrlProviderInterface $fileRequestUrlProvider;

    public function __construct(
        FileReferenceRequestKeyInterface $requestId,
        ClientInterface $client,
        PortalNodeKeyInterface $portalNodeKey,
        FileRequestUrlProviderInterface $fileRequestUrlProvider
    ) {
        $this->requestId = $requestId;
        $this->client = $client;
        $this->portalNodeKey = $portalNodeKey;
        $this->fileRequestUrlProvider = $fileRequestUrlProvider;
    }

    public function getPublicUrl(): string
    {
        // TODO: Add token for one-time permission
        return (string) $this->fileRequestUrlProvider->resolve($this->portalNodeKey, $this->requestId);
    }

    public function getContents(): string
    {
        /** @var RequestInterface $request */
        $request = null; // TODO: $this->requestStorage->load($this->requestId);

        return $this->client->sendRequest($request)->getBody()->getContents();
    }
}
