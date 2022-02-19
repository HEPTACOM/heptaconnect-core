<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;

class ContentsFileReference extends FileReferenceContract
{
    private string $normalizedStream;

    public function __construct(string $normalizedStream)
    {
        $this->normalizedStream = $normalizedStream;
    }

    public function getNormalizedStream(): ?string
    {
        return $this->normalizedStream;
    }
}
