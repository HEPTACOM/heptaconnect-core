<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Core\Storage\Contract;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Psr\Http\Message\RequestInterface;

abstract class RequestStorageContract
{
    /**
     * Loads a PSR-7 request object of a file reference from storage.
     */
    abstract public function load(
        PortalNodeKeyInterface $portalNodeKey,
        FileReferenceRequestKeyInterface $fileReferenceRequestKey
    ): RequestInterface;

    /**
     * Persists a PSR-7 request object to storage and returns a storage-key for it.
     */
    abstract public function persist(
        PortalNodeKeyInterface $portalNodeKey,
        RequestInterface $request
    ): FileReferenceRequestKeyInterface;
}
