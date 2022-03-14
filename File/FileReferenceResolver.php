<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\Bridge\File\FileContentsUrlProviderInterface;
use Heptacom\HeptaConnect\Core\Bridge\File\FileRequestUrlProviderInterface;
use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\File\ResolvedReference\ResolvedContentsFileReference;
use Heptacom\HeptaConnect\Core\File\ResolvedReference\ResolvedPublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\ResolvedReference\ResolvedRequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\RequestStorage;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceResolverContract;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;

class FileReferenceResolver extends FileReferenceResolverContract
{
    private RequestFactoryInterface $requestFactory;

    private FileContentsUrlProviderInterface $fileContentsUrlProvider;

    private FileRequestUrlProviderInterface $fileRequestUrlProvider;

    private NormalizationRegistryContract $normalizationRegistry;

    private RequestStorage $requestStorage;

    private ?HttpClientContract $httpClient = null;

    public function __construct(
        FileContentsUrlProviderInterface $fileContentsUrlProvider,
        FileRequestUrlProviderInterface $fileRequestUrlProvider,
        NormalizationRegistryContract $normalizationRegistry,
        RequestStorage $requestStorage
    ) {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->fileContentsUrlProvider = $fileContentsUrlProvider;
        $this->fileRequestUrlProvider = $fileRequestUrlProvider;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->requestStorage = $requestStorage;
    }

    public function setHttpClient(HttpClientContract $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function resolve(FileReferenceContract $fileReference): ResolvedFileReferenceContract
    {
        if ($fileReference instanceof PublicUrlFileReference) {
            return new ResolvedPublicUrlFileReference(
                $fileReference->getPortalNodeKey(),
                $fileReference->getPublicUrl(),
                $this->httpClient,
                $this->requestFactory
            );
        } elseif ($fileReference instanceof RequestFileReference) {
            return new ResolvedRequestFileReference(
                $fileReference->getPortalNodeKey(),
                $fileReference->getRequestId(),
                $this->httpClient,
                $this->fileRequestUrlProvider,
                $this->requestStorage
            );
        } elseif ($fileReference instanceof ContentsFileReference) {
            $streamDenormalizer = $this->normalizationRegistry->getDenormalizer($fileReference->getNormalizationType());

            if (!$streamDenormalizer instanceof DenormalizerInterface) {
                // TODO: Add custom exception code (and message)
                throw new \Exception('Some shit was fucked up');
            }

            return new ResolvedContentsFileReference(
                $fileReference->getPortalNodeKey(),
                $fileReference->getNormalizedStream(),
                $fileReference->getMimeType(),
                $streamDenormalizer,
                $this->fileContentsUrlProvider
            );
        }

        // TODO: Add custom exception code
        throw new \Exception('Unsupported source');
    }
}
