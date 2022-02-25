<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\ResolvedReference;

use Heptacom\HeptaConnect\Core\Bridge\File\FileContentsUrlProviderInterface;
use Heptacom\HeptaConnect\Core\Storage\Normalizer\StreamDenormalizer;
use Heptacom\HeptaConnect\Portal\Base\File\ResolvedFileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class ResolvedContentsFileReference extends ResolvedFileReferenceContract
{
    private string $normalizedStream;

    private string $mimeType;

    private StreamDenormalizer $streamDenormalizer;

    private PortalNodeKeyInterface $portalNodeKey;

    private FileContentsUrlProviderInterface $fileContentsUrlProvider;

    public function __construct(
        string $normalizedStream,
        string $mimeType,
        StreamDenormalizer $streamDenormalizer,
        PortalNodeKeyInterface $portalNodeKey,
        FileContentsUrlProviderInterface $fileContentsUrlProvider
    ) {
        $this->normalizedStream = $normalizedStream;
        $this->mimeType = $mimeType;
        $this->streamDenormalizer = $streamDenormalizer;
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
        return $this->streamDenormalizer->denormalize($this->normalizedStream, 'stream')->getContents();
    }
}
