<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Core\Bridge\File\FileRequestUrlProviderInterface;
use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Client\ClientInterface;

final class ResolvedRequestFileReference extends ResolvedFileReferenceContract
{
    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        private FileReferenceRequestKeyInterface $requestId,
        private ClientInterface $client,
        private FileRequestUrlProviderInterface $fileRequestUrlProvider,
        private RequestStorageContract $requestStorage
    ) {
        parent::__construct($portalNodeKey);
    }

    public function getPublicUrl(): string
    {
        return (string) $this->fileRequestUrlProvider->resolve($this->getPortalNodeKey(), $this->requestId);
    }

    public function getContents(): string
    {
        $request = $this->requestStorage->load($this->getPortalNodeKey(), $this->requestId);

        return $this->client->sendRequest($request)->getBody()->getContents();
    }
}
