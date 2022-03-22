<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Message\RequestInterface;

abstract class RequestStorageContract
{
    abstract public function load(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $fileReferenceRequestKey
    ): RequestInterface;

    abstract public function persist(
        PortalNodeKeyInterface $portalNodeKey,
        RequestInterface $request
    ): FileReferenceRequestKeyInterface;
}
