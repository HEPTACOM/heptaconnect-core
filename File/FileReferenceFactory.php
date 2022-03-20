<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\RequestStorage;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FileReferenceFactory extends FileReferenceFactoryContract
{
    private PortalNodeKeyInterface $portalNodeKey;

    private StreamFactoryInterface $streamFactory;

    private NormalizationRegistryContract $normalizationRegistry;

    private RequestStorage $requestStorage;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        StreamFactoryInterface $streamFactory,
        NormalizationRegistryContract $normalizationRegistry,
        RequestStorage $requestStorage
    ) {
        $this->portalNodeKey = $portalNodeKey;
        $this->streamFactory = $streamFactory;
        $this->normalizationRegistry = $normalizationRegistry;
        $this->requestStorage = $requestStorage;
    }

    public function fromPublicUrl(string $publicUrl): FileReferenceContract
    {
        return new PublicUrlFileReference($this->portalNodeKey, $publicUrl);
    }

    public function fromRequest(RequestInterface $request): FileReferenceContract
    {
        $requestKey = $this->requestStorage->persist($this->portalNodeKey, $request);

        return new RequestFileReference($this->portalNodeKey, $requestKey);
    }

    public function fromContents(
        string $contents,
        string $mimeType = 'application/octet-stream'
    ): FileReferenceContract {
        $stream = $this->streamFactory->createStream($contents);
        $stream->rewind();
        $serializableStream = new SerializableStream($stream);
        $streamNormalizer = $this->normalizationRegistry->getNormalizer($serializableStream);

        if (!$streamNormalizer instanceof NormalizerInterface) {
            throw new \LogicException(
                'The NormalizationRegistry is missing a normalizer for streams.',
                1647788744
            );
        }

        $normalizedStream = $streamNormalizer->normalize($serializableStream);

        return new ContentsFileReference($this->portalNodeKey, $normalizedStream, $streamNormalizer->getType(), $mimeType);
    }
}
