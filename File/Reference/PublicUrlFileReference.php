<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class PublicUrlFileReference extends FileReferenceContract
{
    private string $publicUrl;

    public function __construct(PortalNodeKeyInterface $portalNodeKey, string $publicUrl)
    {
        parent::__construct($portalNodeKey);
        $this->publicUrl = $publicUrl;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }
}
