<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;

class PublicUrlFileReference extends FileReferenceContract
{
    private string $publicUrl;

    public function __construct(string $publicUrl)
    {
        $this->publicUrl = $publicUrl;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }
}
