<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamNormalizer;
use Heptacom\HeptaConnect\Core\Storage\RequestStorage;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FileReferenceFactory extends FileReferenceFactoryContract
{
    private PortalNodeKeyInterface $portalNodeKey;

    private StreamFactoryInterface $streamFactory;

    private RequestStorage $requestStorage;

    private ?StreamNormalizer $streamNormalizer = null;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        StreamFactoryInterface $streamFactory,
        RequestStorage $requestStorage,
        NormalizationRegistryContract $normalizationRegistryContract
    ) {
        $this->portalNodeKey = $portalNodeKey;
        $this->streamFactory = $streamFactory;
        $this->requestStorage = $requestStorage;

        $streamNormalizer = $normalizationRegistryContract->getNormalizerByType('stream');

        if ($streamNormalizer instanceof StreamNormalizer) {
            $this->streamNormalizer = $streamNormalizer;
        }
    }

    public function fromPublicUrl(string $publicUrl): FileReferenceContract
    {
        return new PublicUrlFileReference($publicUrl);
    }

    public function fromRequest(RequestInterface $request): FileReferenceContract
    {
        $requestKey = $this->requestStorage->persist($this->portalNodeKey, $request);

        return new RequestFileReference($requestKey);
    }

    public function fromContents(
        string $contents,
        string $mimeType = 'application/octet-stream'
    ): FileReferenceContract {
        if (!$this->streamNormalizer instanceof StreamNormalizer) {
            // TODO: Add code and message here
            throw new \Exception('This makes no sense');
        }

        $stream = $this->streamFactory->createStream($contents);
        $stream->rewind();

        $normalizedStream = $this->streamNormalizer->normalize(new SerializableStream($stream));

        return new ContentsFileReference($normalizedStream, $mimeType);
    }
}
