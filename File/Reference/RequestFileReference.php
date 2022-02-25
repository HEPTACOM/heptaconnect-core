<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\File\Reference;

use Heptacom\HeptaConnect\Dataset\Base\File\FileReferenceContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;

class RequestFileReference extends FileReferenceContract
{
    private FileReferenceRequestKeyInterface $requestId;

    public function __construct(FileReferenceRequestKeyInterface $requestId)
    {
        $this->requestId = $requestId;
    }

    public function getRequestId(): FileReferenceRequestKeyInterface
    {
        return $this->requestId;
    }
}
