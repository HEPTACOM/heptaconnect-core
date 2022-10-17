<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\Contract\RequestStorageContract;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class FileReferenceFactory extends FileReferenceFactoryContract
{
    public function __construct(private PortalNodeKeyInterface $portalNodeKey, private StreamFactoryInterface $streamFactory, private NormalizationRegistryContract $normalizationRegistry, private RequestStorageContract $requestStorage)
    {
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

        if (!\is_string($normalizedStream)) {
            throw new \LogicException(
                'Unable to serialize the given file contents.',
                1648315863
            );
        }

        return new ContentsFileReference($this->portalNodeKey, $normalizedStream, $streamNormalizer->getType(), $mimeType);
    }
}
