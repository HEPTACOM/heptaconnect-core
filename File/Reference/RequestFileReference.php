<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;

final class RequestFileReference extends FileReferenceContract
{
    public function __construct(PortalNodeKeyInterface $portalNodeKey, private FileReferenceRequestKeyInterface $requestId)
    {
        parent::__construct($portalNodeKey);
    }

    public function getRequestId(): FileReferenceRequestKeyInterface
    {
        return $this->requestId;
    }
}
