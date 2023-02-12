<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class ContentsFileReference extends FileReferenceContract
{
    private string $normalizedStream;

    private string $normalizationType;

    private string $mimeType;

    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        string $normalizedStream,
        string $normalizationType,
        string $mimeType
    ) {
        parent::__construct($portalNodeKey);
        $this->normalizedStream = $normalizedStream;
        $this->normalizationType = $normalizationType;
        $this->mimeType = $mimeType;
    }

    public function getNormalizedStream(): string
    {
        return $this->normalizedStream;
    }

    public function getNormalizationType(): string
    {
        return $this->normalizationType;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
