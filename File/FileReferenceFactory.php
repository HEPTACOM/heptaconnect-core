<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File;

use Heptacom\HeptaConnect\Core\File\Reference\ContentsFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\PublicUrlFileReference;
use Heptacom\HeptaConnect\Core\File\Reference\RequestFileReference;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamNormalizer;
use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\File\FileReferenceFactoryContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\SerializableStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FileReferenceFactory extends FileReferenceFactoryContract
{
    private StreamFactoryInterface $streamFactory;

    private StreamNormalizer $streamNormalizer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        StreamNormalizer $streamNormalizer
    ) {
        $this->streamFactory = $streamFactory;
        $this->streamNormalizer = $streamNormalizer;
    }

    public function fromPublicUrl(string $publicUrl): FileReferenceContract
    {
        return new PublicUrlFileReference($publicUrl);
    }

    public function fromRequest(RequestInterface $request): FileReferenceContract
    {
        // TODO: Ensure, that $request is serializable

        return new RequestFileReference($request);
    }

    public function fromContents(string $contents): FileReferenceContract
    {
        $stream = new SerializableStream($this->streamFactory->createStream($contents));
        $normalizedStream = $this->streamNormalizer->normalize($stream);

        return new ContentsFileReference($normalizedStream);
    }
}
