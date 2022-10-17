<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

final class PublicUrlFileReference extends FileReferenceContract
{
    public function __construct(PortalNodeKeyInterface $portalNodeKey, private string $publicUrl)
    {
        parent::__construct($portalNodeKey);
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }
}
