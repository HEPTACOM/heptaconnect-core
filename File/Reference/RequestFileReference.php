<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;

class RequestFileReference extends FileReferenceContract
{
    private FileReferenceRequestKeyInterface $requestId;

    public function __construct(PortalNodeKeyInterface $portalNodeKey, FileReferenceRequestKeyInterface $requestId)
    {
        parent::__construct($portalNodeKey);
        $this->requestId = $requestId;
    }

    public function getRequestId(): FileReferenceRequestKeyInterface
    {
        return $this->requestId;
    }
}
