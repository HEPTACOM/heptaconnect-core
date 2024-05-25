<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class ContentsFileReference extends FileReferenceContract
{
    public function __construct(
        PortalNodeKeyInterface $portalNodeKey,
        private readonly string $normalizedStream,
        private readonly string $normalizationType,
        private readonly string $mimeType
    ) {
        parent::__construct($portalNodeKey);
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
