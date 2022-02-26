<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Core\Bridge\File\FileRequestUrlProviderInterface;
use Heptacom\HeptaConnect\Core\Storage\RequestStorage;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Client\ClientInterface;

class ResolvedRequestFileReference extends ResolvedFileReferenceContract
{
    private FileReferenceRequestKeyInterface $requestId;

    private ClientInterface $client;

    private FileRequestUrlProviderInterface $fileRequestUrlProvider;

    private RequestStorage $requestStorage;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $requestId,
        ClientInterface $client,
        FileRequestUrlProviderInterface $fileRequestUrlProvider,
        RequestStorage $requestStorage
    ) {
        parent::__construct($portalNodeKey);
        $this->requestId = $requestId;
        $this->client = $client;
        $this->fileRequestUrlProvider = $fileRequestUrlProvider;
        $this->requestStorage = $requestStorage;
    }

    public function getPublicUrl(): string
    {
        // TODO: Add token for one-time permission
        return (string) $this->fileRequestUrlProvider->resolve($this->getPortalNodeKey(), $this->requestId);
    }

    public function getContents(): string
    {
        $request = $this->requestStorage->load($this->getPortalNodeKey(), $this->requestId);

        return $this->client->sendRequest($request)->getBody()->getContents();
    }
}
