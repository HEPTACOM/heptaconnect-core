<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Core\Bridge\File\FileContentsUrlProviderInterface;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\Serialization\Contract\DenormalizerInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Psr\Http\Message\StreamInterface;

class ResolvedContentsFileReference extends ResolvedFileReferenceContract
{
    private string $normalizedStream;

    private string $mimeType;

    private DenormalizerInterface $denormalizer;

    private PortalNodeKeyInterface $portalNodeKey;

    private FileContentsUrlProviderInterface $fileContentsUrlProvider;

    public function __construct(
        string $normalizedStream,
        string $mimeType,
        DenormalizerInterface $denormalizer,
        PortalNodeKeyInterface $portalNodeKey,
        FileContentsUrlProviderInterface $fileContentsUrlProvider
    ) {
        $this->normalizedStream = $normalizedStream;
        $this->mimeType = $mimeType;
        $this->denormalizer = $denormalizer;
        $this->portalNodeKey = $portalNodeKey;
        $this->fileContentsUrlProvider = $fileContentsUrlProvider;
    }

    public function getPublicUrl(): string
    {
        return (string) $this->fileContentsUrlProvider->resolve(
            $this->portalNodeKey,
            $this->normalizedStream,
            $this->mimeType
        );
    }

    public function getContents(): string
    {
        $stream = $this->denormalizer->denormalize($this->normalizedStream, $this->denormalizer->getType());

        if (!$stream instanceof StreamInterface) {
            throw new \Exception('Stream is not a stream');
        }

        return $stream->getContents();
    }
}
