<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamNormalizer;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\NormalizationRegistryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FileReferenceFactory extends FileReferenceFactoryContract
{
    private StreamFactoryInterface $streamFactory;

    private ?StreamNormalizer $streamNormalizer = null;

    private NormalizationRegistryContract $normalizationRegistryContract;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        NormalizationRegistryContract $normalizationRegistryContract
    ) {
        $this->streamFactory = $streamFactory;
        $this->normalizationRegistryContract = $normalizationRegistryContract;

        $streamNormalizer = $this->normalizationRegistryContract->getNormalizerByType('stream');

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
        // TODO: Implement requestStorage

        /** @var FileReferenceRequestKeyInterface $requestId */
        $requestId = null; // TODO: $this->requestStorage->store($request);

        return new RequestFileReference($requestId);
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
