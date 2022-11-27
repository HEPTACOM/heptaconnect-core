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
use Heptacom\HeptaConnect\Core\Portal\PortalStackServiceContainerFactory;
use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceResolverContract;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Message\RequestFactoryInterface;

final class FileReferenceResolver extends FileReferenceResolverContract
{
    private RequestFactoryInterface $requestFactory;

    public function __construct(
        private FileContentsUrlProviderInterface $fileContentsUrlProvider,
        private FileRequestUrlProviderInterface $fileRequestUrlProvider,
        private NormalizationRegistryContract $normalizationRegistry,
        private RequestStorageContract $requestStorage,
        private PortalStackServiceContainerFactory $portalStackServiceContainerFactory
    ) {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
    }

    public function resolve(FileReferenceContract $fileReference): ResolvedFileReferenceContract
    {
        if ($fileReference instanceof PublicUrlFileReference) {
            $portalNodeKey = $fileReference->getPortalNodeKey();
            $httpClient = $this->portalStackServiceContainerFactory->create($portalNodeKey)->getWebHttpClient();

            return new ResolvedPublicUrlFileReference(
                $portalNodeKey,
                $fileReference->getPublicUrl(),
                $httpClient,
                $this->requestFactory
            );
        } elseif ($fileReference instanceof RequestFileReference) {
            $portalNodeKey = $fileReference->getPortalNodeKey();
            $httpClient = $this->portalStackServiceContainerFactory->create($portalNodeKey)->getWebHttpClient();

            return new ResolvedRequestFileReference(
                $portalNodeKey,
                $fileReference->getRequestId(),
                $httpClient,
                $this->fileRequestUrlProvider,
                $this->requestStorage
            );
        } elseif ($fileReference instanceof ContentsFileReference) {
            $streamDenormalizer = $this->normalizationRegistry->getDenormalizer($fileReference->getNormalizationType());

            if (!$streamDenormalizer instanceof DenormalizerInterface) {
                throw new \LogicException(
                    'The NormalizationRegistry is missing a denormalizer for streams.',
                    1647788896
                );
            }

            return new ResolvedContentsFileReference(
                $fileReference->getPortalNodeKey(),
                $fileReference->getNormalizedStream(),
                $fileReference->getMimeType(),
                $streamDenormalizer,
                $this->fileContentsUrlProvider
            );
        }

        throw new \InvalidArgumentException(
            'FileReference of unsupported source: ' . $fileReference::class,
            1647789133
        );
    }
}
