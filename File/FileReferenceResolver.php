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
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamDenormalizer;
use Heptacom\HeptaConnect\Core\Storage\RequestStorage;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceResolverContract;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Web\Http\Contract\HttpClientContract;
use Psr\Http\Message\RequestFactoryInterface;

class FileReferenceResolver extends FileReferenceResolverContract
{
    private ?StreamDenormalizer $streamDenormalizer = null;

    private HttpClientContract $httpClient;

    private RequestFactoryInterface $requestFactory;

    private PortalNodeKeyInterface $portalNodeKey;

    private FileContentsUrlProviderInterface $fileContentsUrlProvider;

    private FileRequestUrlProviderInterface $fileRequestUrlProvider;

    private RequestStorage $requestStorage;

    public function __construct(
        HttpClientContract $httpClient,
        RequestFactoryInterface $requestFactory,
        PortalNodeKeyInterface $portalNodeKey,
        FileContentsUrlProviderInterface $fileContentsUrlProvider,
        FileRequestUrlProviderInterface $fileRequestUrlProvider,
        NormalizationRegistryContract $normalizationRegistryContract,
        RequestStorage $requestStorage
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->portalNodeKey = $portalNodeKey;
        $this->fileContentsUrlProvider = $fileContentsUrlProvider;
        $this->fileRequestUrlProvider = $fileRequestUrlProvider;
        $this->requestStorage = $requestStorage;

        $streamDenormalizer = $normalizationRegistryContract->getDenormalizer('stream');

        if ($streamDenormalizer instanceof StreamDenormalizer) {
            $this->streamDenormalizer = $streamDenormalizer;
        }
    }

    public function resolve(FileReferenceContract $fileReference): ResolvedFileReferenceContract
    {
        if ($fileReference instanceof PublicUrlFileReference) {
            return new ResolvedPublicUrlFileReference(
                $fileReference->getPublicUrl(),
                $this->httpClient,
                $this->requestFactory
            );
        } elseif ($fileReference instanceof RequestFileReference) {
            return new ResolvedRequestFileReference(
                $fileReference->getRequestId(),
                $this->httpClient,
                $this->portalNodeKey,
                $this->fileRequestUrlProvider,
                $this->requestStorage
            );
        } elseif ($fileReference instanceof ContentsFileReference) {
            if (!$this->streamDenormalizer instanceof StreamDenormalizer) {
                // TODO: Add custom exception code (and message)
                throw new \Exception('Some shit was fucked up');
            }

            return new ResolvedContentsFileReference(
                $fileReference->getNormalizedStream(),
                $fileReference->getMimeType(),
                $this->streamDenormalizer,
                $this->portalNodeKey,
                $this->fileContentsUrlProvider
            );
        }

        // TODO: Add custom exception code
        throw new \Exception('Unsupported source');
    }
}
