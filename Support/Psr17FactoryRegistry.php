<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Support;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class Psr17FactoryRegistry
{
    private RequestFactoryInterface $requestFactory;

    private ResponseFactoryInterface $responseFactory;

    private ServerRequestFactoryInterface $serverRequestFactory;

    private StreamFactoryInterface $streamFactory;

    private UploadedFileFactoryInterface $uploadedFileFactory;

    private UriFactoryInterface $uriFactory;

    public function __construct(
        ?RequestFactoryInterface $requestFactory = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?ServerRequestFactoryInterface $serverRequestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?UploadedFileFactoryInterface $uploadedFileFactory = null,
        ?UriFactoryInterface $uriFactory = null
    ) {
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->serverRequestFactory = $serverRequestFactory ?? Psr17FactoryDiscovery::findServerRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->uploadedFileFactory = $uploadedFileFactory ?? Psr17FactoryDiscovery::findUploadedFileFactory();
        $this->uriFactory = $uriFactory ?? Psr17FactoryDiscovery::findUriFactory();
    }

    public function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    public function getServerRequestFactory(): ServerRequestFactoryInterface
    {
        return $this->serverRequestFactory;
    }

    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    public function getUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return $this->uploadedFileFactory;
    }

    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }
}
